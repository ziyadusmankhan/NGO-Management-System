<?php

namespace App\Domain\Documents\Services;

use App\Domain\Audit\Services\AuditRecorder;
use App\Domain\Business\Models\BusinessDocument;
use App\Domain\Documents\Models\LibraryDocument;
use App\Domain\Procurement\Models\ProcurementDocument;
use App\Domain\Projects\Models\ProjectDocument;
use App\Domain\Qard\Models\QardDocument;
use App\Domain\Welfare\Applications\Models\WelfareApplication;
use App\Domain\Welfare\Applications\Models\WelfareApplicationAgreement;
use App\Domain\Welfare\Verification\Models\BeneficiaryVerification;
use App\Domain\Welfare\Verification\Models\BeneficiaryVerificationEvidence;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentRegistry
{
    public const SOURCES = [
        QardDocument::class => ['qard', 'qard_loan_id'],
        BusinessDocument::class => ['business', 'business_application_id'],
        ProjectDocument::class => ['projects', 'project_id'],
        ProcurementDocument::class => ['procurement', 'procurement_id'],
        BeneficiaryVerificationEvidence::class => ['verification', 'verification_id'],
    ];

    public function ready(): bool
    {
        if (Schema::hasTable('library_documents')) {
            return true;
        }
        if (Schema::hasTable('migrations') && DB::table('migrations')->where('migration', '2026_10_27_000001_create_document_library_and_account_security')->exists()) {
            throw new \RuntimeException('Required document registry is unavailable.');
        }

        return false;
    }

    public function reference(Model $m): string
    {
        return (string) ($m->number ?? $m->application_number ?? $m->verification_number ?? class_basename($m).' #'.$m->id);
    }

    public function entries(Model $m, bool $historical = false): array
    {
        if (! $this->ready()) {
            return [];
        }
        if ($m instanceof WelfareApplicationAgreement) {
            $result = [];
            foreach (['beneficiary', 'organization'] as $party) {
                $path = $m->{$party.'_document_path'};
                if ($path) {
                    $result[] = ['welfare_agreement:'.$m->id.':'.$party, 'welfare', $m->application_id, 'Agreement', 'private', $path, null, $m->{$party.'_signature_verified_by'}, $m->{$party.'_signed_at'}, $historical];
                }
            }
            if ($m->document_path && ! in_array($m->document_path, [$m->beneficiary_document_path, $m->organization_document_path])) {
                $result[] = ['welfare_agreement:'.$m->id.':legacy', 'welfare', $m->application_id, 'Agreement', 'private', $m->document_path, null, null, null, true];
            }

            return $result;
        }
        [$module,$foreign] = self::SOURCES[get_class($m)];
        $parent = $m->{$foreign};
        $key = $module.':'.$m->id;
        if ($module === 'verification') {
            $verification = BeneficiaryVerification::find($parent);
            if (! $verification?->application_id) {
                return [];
            } $parent = $verification->application_id;
            $module = 'welfare';
        }
        $kind = strtolower($m->kind ?? '');
        $category = $key[0] === 'v' ? 'Verification' : (preg_match('/borrower|foundation|agreement|signature/', $kind) ? 'Agreement' : (preg_match('/quote/', $kind) ? 'Quotation' : 'Other'));

        return [[$key, $module, $parent, $category, $m->disk ?? 'private', $m->path, $m->original_name, $m->actor_id ?? $m->uploaded_by, $m->created_at, $historical]];
    }

    public function source(Model $m, bool $historical = false): array
    {
        return array_map(fn ($entry) => $this->register(...$entry), $this->entries($m, $historical));
    }

    public function register(string $key, string $module, int $parentId, string $category, string $disk, string $path, ?string $name, ?int $actor, $time, bool $historical = false): LibraryDocument
    {
        return DB::transaction(function () use ($key, $module, $parentId, $category, $disk, $path, $name, $actor, $time, $historical) {
            if ($d = LibraryDocument::where('source_key', $key)->first()) {
                return $d;
            }
            $parent = app(DocumentAccess::class)->parent($module, $parentId);
            $exists = in_array($disk, ['private', 'local'], true) && $this->safePath($disk, $path) && Storage::disk($disk)->exists($path);
            $d = LibraryDocument::create(['number' => 'DOC-'.Str::ulid(), 'title' => $this->reference($parent).' — '.$category, 'category' => $category, 'module' => $module, 'parent_type' => $module, 'parent_id' => $parentId, 'parent_reference' => $this->reference($parent), 'source_key' => $key, 'authoritative' => true, 'owner_id' => $actor]);
            $d->versions()->create(['version' => 1, 'disk' => $disk, 'path' => $path, 'original_name' => $name, 'mime_type' => $exists ? Storage::disk($disk)->mimeType($path) : null, 'size' => $exists ? Storage::disk($disk)->size($path) : null, 'sha256' => $exists ? hash_file('sha256', Storage::disk($disk)->path($path)) : null, 'status' => $exists ? 'available' : 'missing', 'validation_status' => $historical ? 'legacy_unscanned' : 'validation_only', 'uploaded_by' => $actor, 'uploaded_at' => $time]);
            $this->audit($d, $historical ? 'document_imported' : 'document_registered');

            return $d;
        });
    }

    public function safePath(string $disk, string $path): bool
    {
        if (! in_array($disk, ['private', 'local'], true) || $path === '' || str_contains($path, '\\') || str_contains($path, ':') || str_starts_with($path, '/') || in_array('..', explode('/', $path), true)) {
            return false;
        }
        $root = realpath(Storage::disk($disk)->path(''));
        $actual = realpath(Storage::disk($disk)->path($path));

        return $root && (! $actual || str_starts_with($actual, $root.DIRECTORY_SEPARATOR));
    }

    public function audit(LibraryDocument $d, string $action, array $data = []): void
    {
        app(AuditRecorder::class)->record('documents', $action, ['subject_type' => get_class($d), 'subject_id' => $d->id, 'subject_reference' => $d->number] + $data);
    }

    public function import(bool $dry = false): array
    {
        $stats = ['sources' => 0, 'existing' => 0, 'imported' => 0, 'missing' => 0];
        foreach (array_merge(array_keys(self::SOURCES), [WelfareApplicationAgreement::class]) as $class) {
            $class::query()->chunkById(100, function ($rows) use (&$stats, $dry) {
                foreach ($rows as $m) {
                    foreach ($this->entries($m, true) as $entry) {
                        $stats['sources']++;
                        [$key,$module,$parent,$category,$disk,$path] = $entry;
                        $exists = LibraryDocument::where('source_key', $key)->exists();
                        $stats[$exists ? 'existing' : 'imported']++;
                        if (! $this->safePath($disk, $path) || ! Storage::disk($disk)->exists($path)) {
                            $stats['missing']++;
                        }
                        if (! $dry && ! $exists) {
                            $this->register(...$entry);
                        }
                    }
                }
            });
        }
        WelfareApplication::query()->chunkById(100, function ($rows) use (&$stats, $dry) {
            foreach ($rows as $application) {
                foreach (Storage::disk('private')->files('welfare/applications/'.$application->id) as $path) {
                    $key = 'welfare_file:'.$application->id.':'.hash('sha256', $path);
                    $stats['sources']++;
                    if (LibraryDocument::where('source_key', $key)->exists()) {
                        $stats['existing']++;

                        continue;
                    }
                    if (! $dry) {
                        $this->register($key, 'welfare', $application->id, 'Other', 'private', $path, null, null, null, true);
                    }$stats['imported']++;
                }
            }
        });
        if (! $dry) {
            app(AuditRecorder::class)->record('documents', 'document_import_completed', ['after' => ['source_count' => $stats['sources'], 'imported_count' => $stats['imported'], 'existing_count' => $stats['existing']]]);
        }

        return $stats;
    }
}
