<?php

namespace Tests\Feature;

use App\Domain\Documents\Services\DocumentService;
use App\Domain\Operations\Models\Task;
use App\Domain\Organization\Services\SectionRegister;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use setasign\Fpdi\PdfParser\PdfParser;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\PdfReader\PdfReader;
use Tests\Support\CreatesWelfareFixtures;
use Tests\Support\ValidUpload;
use Tests\TestCase;

class SectionExportTest extends TestCase
{
    use CreatesWelfareFixtures, RefreshDatabase;

    public static function formats(): array
    {
        $cases = [];
        foreach (array_keys(SectionRegister::TYPES) as $kind) {
            foreach (['pdf', 'xlsx'] as $format) {
                $cases[] = [$kind, $format];
            }
        }

        return $cases;
    }

    #[DataProvider('formats')]
    public function test_permission_checked_real_branded_files_with_a4_portrait_pages(string $kind, string $format): void
    {
        $denied = $this->staff([]);
        $url = route('sections.export', ['kind' => $kind, 'format' => $format]);
        $this->actingAs($denied)->get($url)->assertForbidden();
        $actor = $this->staff(array_values(array_unique([...SectionRegister::TYPES[$kind][1], 'tasks.view', 'meetings.view'])));
        $response = $this->actingAs($actor)->get($url)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        if ($format === 'pdf') {
            $pdf = new PdfReader(new PdfParser(StreamReader::createByString($response->getContent())));
            $this->assertGreaterThan(0, $pdf->getPageCount());
            for ($page = 1; $page <= $pdf->getPageCount(); $page++) {
                $size = $pdf->getPage($page)->getBoundary();
                $this->assertEqualsWithDelta(595.28, $size->getWidth(), 1);
                $this->assertEqualsWithDelta(841.89, $size->getHeight(), 1);
            }
        } else {
            $path = tempnam(sys_get_temp_dir(), 'section-');
            file_put_contents($path, $response->getContent());
            try {
                $book = IOFactory::load($path);
                $this->assertGreaterThan(0, $book->getActiveSheet()->getDrawingCollection()->count());
                $this->assertGreaterThanOrEqual(0, $book->getActiveSheet()->getCell('B6')->getCalculatedValue());
                $book->disconnectWorksheets();
            } finally {
                unlink($path);
            }
        }
    }

    public function test_task_filters_all_pages_and_formula_safe_text(): void
    {
        $a = $this->staff(['tasks.view']);
        for ($i = 1; $i <= 27; $i++) {
            Task::create(['number' => 'TSK-'.$i, 'title' => '=HYPERLINK("https://example.invalid")', 'category' => 'other', 'priority' => 'high', 'status' => 'pending', 'due_on' => '2026-11-02', 'created_by' => $a->id]);
        }
        Task::create(['number' => 'EXCLUDED', 'title' => 'Other', 'category' => 'other', 'priority' => 'low', 'status' => 'pending', 'due_on' => '2026-11-02', 'created_by' => $a->id]);
        $response = $this->actingAs($a)->get(route('sections.export', ['kind' => 'tasks', 'format' => 'xlsx', 'priority' => 'high', 'page' => 2]))->assertOk();
        $path = tempnam(sys_get_temp_dir(), 'tasks-');
        file_put_contents($path, $response->getContent());
        try {
            $book = IOFactory::load($path);
            $sheet = $book->getActiveSheet();
            $this->assertSame(27, $sheet->getCell('B6')->getCalculatedValue());
            $this->assertSame('s', $sheet->getCell('C16')->getDataType());
            $this->assertStringNotContainsString('EXCLUDED', json_encode($sheet->toArray()));
            $book->disconnectWorksheets();
        } finally {
            unlink($path);
        }
    }

    public function test_document_export_respects_role_sharing_and_never_exposes_storage_paths(): void
    {
        Storage::fake('private');
        $owner = $this->staff(['documents.view', 'documents.upload', 'documents.share']);
        $other = $this->staff(['documents.view']);
        $document = app(DocumentService::class)->save($owner, ['title' => 'Restricted policy', 'category' => 'Policy', 'document' => ValidUpload::pdf()]);
        $request = Request::create('/exports/sections/documents');
        $request->setUserResolver(fn () => $other);
        $service = app(SectionRegister::class);
        $this->assertSame(0, $service->build('documents', $request)['count']);
        $document->roles()->attach($other->roles()->first()->id);
        $visible = $service->build('documents', $request);
        $this->assertSame(1, $visible['count']);
        $this->assertStringNotContainsString($document->versions()->first()->path, json_encode($visible['sections']));
        $document->roles()->detach();
        $this->assertSame(0, $service->build('documents', $request)['count']);
    }
}
