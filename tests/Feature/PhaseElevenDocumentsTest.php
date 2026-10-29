<?php

namespace Tests\Feature;

use App\Domain\Audit\Models\AuditEvent;
use App\Domain\Documents\Models\LibraryDocument;
use App\Domain\Documents\Services\DocumentRegistry;
use App\Domain\Documents\Services\DocumentService;
use App\Domain\Documents\Services\UploadValidator;
use App\Domain\Users\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\Support\CreatesWelfareFixtures;
use Tests\Support\ValidUpload;
use Tests\TestCase;

class PhaseElevenDocumentsTest extends TestCase
{
    use CreatesWelfareFixtures,RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
    }

    private function officer()
    {
        return $this->staff(array_map(fn ($a) => 'documents.'.$a, ['view', 'download', 'upload', 'manage', 'share', 'archive', 'restore']));
    }

    private function upload($u, string $title = 'Private policy')
    {
        return app(DocumentService::class)->save($u, ['title' => $title, 'category' => 'Policy', 'document' => ValidUpload::pdf()]);
    }

    public function test_metadata_counts_and_downloads_require_current_role_or_owner_access(): void
    {
        $owner = $this->officer();
        $other = $this->officer();
        $d = $this->upload($owner);
        $this->actingAs($other)->get(route('records.documents.index'))->assertOk()->assertDontSee('Private policy');
        $this->get(route('records.documents.show', $d))->assertNotFound();
        $this->get(route('records.documents.download', [$d, $d->versions()->first()]))->assertNotFound();
        app(DocumentService::class)->change($owner, $d, 'share', ['revision' => 1, 'roles' => [$other->roles()->first()->id]]);
        $this->get(route('records.documents.index'))->assertSee('Private policy');
        $this->get(route('records.documents.download', [$d, $d->versions()->first()]))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $other->roles()->detach();
        $this->get(route('records.documents.index'))->assertForbidden();
    }

    public function test_versions_remain_immutable_and_archiving_does_not_delete_bytes(): void
    {
        $owner = $this->officer();
        $d = $this->upload($owner);
        $first = $d->versions()->first();
        app(DocumentService::class)->save($owner, ['revision' => 1, 'title' => $d->title, 'category' => 'Policy', 'document' => ValidUpload::pdf('new.pdf')], $d);
        $this->assertSame(2, $d->versions()->count());
        $this->assertSame($first->sha256, hash_file('sha256', Storage::disk('private')->path($first->path)));
        app(DocumentService::class)->change($owner, $d->fresh(), 'archive', ['revision' => 2]);
        $this->assertNotNull($d->fresh()->archived_at);
        Storage::disk('private')->assertExists($first->path);
        app(DocumentService::class)->change($owner, $d->fresh(), 'restore', ['revision' => 3]);
        $this->assertNull($d->fresh()->archived_at);
        $this->expectException(\LogicException::class);
        $first->update(['path' => 'replacement.pdf']);
    }

    public function test_source_evidence_keeps_original_permissions_and_import_is_read_only_then_idempotent(): void
    {
        $actor = $this->staff();
        $application = $this->signedApplication($actor);
        $count = LibraryDocument::count();
        $this->assertSame(2, $count);
        $before = AuditEvent::count();
        $this->artisan('documents:import', ['--dry-run' => true])->assertExitCode(0);
        $this->assertSame($before, AuditEvent::count());
        $this->assertSame($count, LibraryDocument::count());
        $this->artisan('documents:import')->assertExitCode(0);
        $this->artisan('documents:import')->assertExitCode(0);
        $this->assertSame($count, LibraryDocument::count());
        $reader = $this->officer();
        $d = LibraryDocument::first();
        $this->actingAs($reader)->get(route('records.documents.show', $d))->assertNotFound();
        $permission = Permission::where('slug', 'welfare.agreements.view')->first();
        $reader->roles()->first()->permissions()->attach($permission);
        $this->get(route('records.documents.show', $d))->assertOk()->assertSee('Linked records preserve');
        $this->post(route('records.documents.change', [$d, 'archive']), ['revision' => 1])->assertForbidden();
    }

    public function test_new_upload_cannot_satisfy_workflow_evidence_and_paths_are_not_exposed(): void
    {
        $owner = $this->officer();
        $actor = $this->staff();
        $a = $this->signedApplication($actor);
        $this->actingAs($owner)->post(route('records.documents.store'), ['title' => 'Added note', 'category' => 'Agreement', 'parent_type' => 'welfare', 'parent_id' => $a->id, 'document' => ValidUpload::pdf()])->assertForbidden();
        $d = $this->upload($owner);
        $v = $d->versions()->first();
        $this->get(route('records.documents.show', $d))->assertOk()->assertDontSee($v->path);
        $this->get('/storage/'.$v->path)->assertNotFound();
    }

    public function test_checksum_mismatch_missing_file_and_wrong_document_version_fail(): void
    {
        $owner = $this->officer();
        $d = $this->upload($owner);
        $v = $d->versions()->first();
        $other = $this->upload($owner);
        $this->actingAs($owner)->get(route('records.documents.download', [$other, $v]))->assertNotFound();
        Storage::disk('private')->put($v->path, 'tampered');
        $this->get(route('records.documents.download', [$d, $v]))->assertStatus(409);
        Storage::disk('private')->delete($v->path);
        $this->get(route('records.documents.download', [$d, $v]))->assertNotFound();
        $this->assertFalse(app(DocumentRegistry::class)->safePath('private', '../.env'));
        $this->assertFalse(app(DocumentRegistry::class)->safePath('public', 'documents/example.pdf'));
    }

    public function test_upload_validation_rejects_spoofed_oversized_and_executable_files_without_records(): void
    {
        $owner = $this->officer();
        foreach ([UploadedFile::fake()->createWithContent('spoof.pdf', 'executable text'), UploadedFile::fake()->create('large.pdf', 10241, 'application/pdf'), UploadedFile::fake()->createWithContent('bad.exe', 'MZ binary')] as $file) {
            $this->actingAs($owner)->post(route('records.documents.store'), ['title' => 'Invalid', 'category' => 'Other', 'document' => $file])->assertSessionHasErrors();
        }
        $this->assertSame(0, LibraryDocument::count());
        $this->assertCount(0, Storage::disk('private')->allFiles());
    }

    private function office(array $extra = [], string $name = 'file.docx'): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'phase11-office-');
        $zip = new \ZipArchive;
        $zip->open($path, \ZipArchive::OVERWRITE);
        $parts = ['[Content_Types].xml' => '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>', 'word/document.xml' => '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body/></w:document>'] + $extra;
        $largePart = null;
        foreach ($parts as $key => $value) {
            if ($value === null) {
                // A sparse file exercises expansion limits without large fixture strings in PHP memory.
                $largePart = tempnam(sys_get_temp_dir(), 'phase11-expanded-');
                $handle = fopen($largePart, 'wb');
                ftruncate($handle, 21 * 1024 * 1024);
                fclose($handle);
                $zip->addFile($largePart, $key);
            } else {
                $zip->addFromString($key, $value);
            }
        }
        $zip->close();
        if ($largePart !== null) {
            unlink($largePart);
        }
        $bytes = file_get_contents($path);
        unlink($path);

        return UploadedFile::fake()->createWithContent($name, $bytes);
    }

    public function test_office_container_inspection_rejects_embedded_objects_external_templates_and_entities(): void
    {
        $v = app(UploadValidator::class);
        $this->assertSame('docx', $v->inspect($this->office())['extension']);
        foreach ([
            ['word/embeddings/thing.bin' => 'MZ'],
            ['word/media/hidden.dat' => 'MZ hidden executable'],
            ['word/_rels/document.xml.rels' => '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="r1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/attachedTemplate" TargetMode="External" Target="https://example.test/template"/></Relationships>'],
            ['word/danger.xml' => '<!DOCTYPE foo [<!ENTITY x SYSTEM "file:///secret">]><foo>&x;</foo>'],
            ['xl/connections.xml' => '<connections/>'],
            ['word/large.xml' => null],
        ] as $extra) {
            try {
                $v->inspect($this->office($extra));
                $this->fail('Unsafe Office accepted.');
            } catch (ValidationException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_missing_legacy_file_is_explicit_without_invented_upload_metadata(): void
    {
        $a = $this->signedApplication($this->staff());
        $d = app(DocumentRegistry::class)->register('test-missing', 'welfare', $a->id, 'Other', 'private', 'welfare/missing.pdf', null, null, null, true);
        $v = $d->versions()->first();
        $this->assertSame('missing', $v->status);
        $this->assertNull($v->uploaded_at);
        $this->assertNull($v->uploaded_by);
        $this->assertNull($v->original_name);
    }

    public function test_document_screens_sharing_confirmation_and_stale_versions(): void
    {
        $owner = $this->officer();
        $d = $this->upload($owner);
        $this->actingAs($owner)->get(route('records.documents.create'))->assertOk();
        $this->get(route('records.documents.index'))->assertOk()->assertSee('Documents Library');
        $this->withSession(['security.confirmed' => 0])->post(route('records.documents.change', [$d, 'share']), ['revision' => 1])->assertSessionHasErrors('password');
        $this->withSession(['security.confirmed' => time()])->post(route('records.documents.change', [$d, 'manage']), ['revision' => 99, 'title' => 'New', 'category' => 'Policy'])->assertSessionHasErrors('revision');
        $this->assertSame('Private policy', $d->fresh()->title);
    }
}
