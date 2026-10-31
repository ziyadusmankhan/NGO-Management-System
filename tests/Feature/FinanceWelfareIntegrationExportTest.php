<?php

namespace Tests\Feature;

use App\Domain\Finance\Transactions\Models\Transaction;
use App\Domain\Organization\Services\FinanceWelfareReport;
use App\Domain\Procurement\Services\ProcurementPaymentService;
use App\Domain\Projects\Services\ProjectPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Support\CreatesProcurementFixtures;
use Tests\TestCase;

class FinanceWelfareIntegrationExportTest extends TestCase
{
    use CreatesProcurementFixtures,RefreshDatabase;

    public function test_finance_expenses_include_controlled_payments_once_and_apply_category_filters(): void
    {
        DB::table('procurement_policies')->insertOrIgnore(['id' => 1, 'quote_count' => 2, 'created_at' => now(), 'updated_at' => now()]);
        $officer = $this->projectActor();
        $project = $this->approveProject($this->draftProject($officer), $officer);
        $projectPayment = app(ProjectPaymentService::class)->record($project, 'payment', $this->projectPayment($project, '10.25'), $this->projectActor());
        $purchaser = $this->procurementActor();
        $purchase = $this->purchaseApprove($this->purchaseDraft($purchaser), $purchaser);
        $line = $this->purchaseInvoice($purchase, $purchaser);
        $officePayment = app(ProcurementPaymentService::class)->record($purchase, 'pay', $this->purchasePayment($purchase, $line, '20.25'), $this->procurementActor());
        $ledgerBefore = Transaction::count();
        $viewer = $this->staff(['finance.view']);
        $report = app(FinanceWelfareReport::class)->build('expenses', [], $viewer);
        $this->assertSame(2, $report['count']);
        $this->assertSame('30.50', $report['metrics'][2]['value']);
        $this->assertCount(0, $report['sections'][0]['rows']);
        $this->assertCount(1, $report['sections'][1]['rows']);
        $this->assertCount(1, $report['sections'][2]['rows']);
        $this->actingAs($viewer)->get(route('finance.expenses.index'))->assertOk()->assertSee($project->number)->assertSee($purchase->number);
        $response = $this->get(route('finance.expenses.export', ['format' => 'xlsx']))->assertOk();
        $path = tempnam(sys_get_temp_dir(), 'controlled-export-');
        file_put_contents($path, $response->getContent());
        try {
            $book = IOFactory::load($path);
            $this->assertSame(2, $book->getSheet(0)->getCell('B6')->getCalculatedValue());
            $this->assertEquals(30.50, $book->getSheet(0)->getCell('D6')->getCalculatedValue());
            $book->disconnectWorksheets();
        } finally {
            unlink($path);
        }
        $projects = app(FinanceWelfareReport::class)->build('expenses', ['category' => 'project'], $viewer);
        $this->assertSame(1, $projects['count']);
        $this->assertSame('10.25', $projects['metrics'][2]['value']);
        $office = app(FinanceWelfareReport::class)->build('expenses', ['category' => 'admin'], $viewer);
        $this->assertSame(1, $office['count']);
        $this->assertSame('20.25', $office['metrics'][2]['value']);
        $this->assertSame($ledgerBefore, Transaction::count());
    }
}
