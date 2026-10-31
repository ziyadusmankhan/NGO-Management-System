<?php

namespace Tests\Feature;

use App\Domain\Finance\Transactions\Models\Transaction;
use App\Domain\Organization\Services\SectionRegister;
use App\Domain\Procurement\Services\ProcurementPaymentService;
use App\Domain\Projects\Services\ProjectPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesProcurementFixtures;
use Tests\TestCase;

class SectionFinancialExportTest extends TestCase
{
    use CreatesProcurementFixtures, RefreshDatabase;

    public function test_project_and_procurement_reports_preserve_decimal_balances_without_posting(): void
    {
        DB::table('procurement_policies')->insertOrIgnore(['id' => 1, 'quote_count' => 2, 'created_at' => now(), 'updated_at' => now()]);
        $officer = $this->projectActor();
        $project = $this->approveProject($this->draftProject($officer), $officer);
        app(ProjectPaymentService::class)->record($project, 'payment', $this->projectPayment($project, '10.25'), $this->projectActor());
        $buyer = $this->procurementActor();
        $purchase = $this->purchaseApprove($this->purchaseDraft($buyer), $buyer);
        $line = $this->purchaseInvoice($purchase, $buyer);
        app(ProcurementPaymentService::class)->record($purchase, 'pay', $this->purchasePayment($purchase, $line, '10.25'), $this->procurementActor());
        $before = Transaction::count();
        $request = Request::create('/exports/sections/project-report');
        $request->setUserResolver(fn () => $buyer);
        $service = app(SectionRegister::class);
        $projectReport = $service->build('project-report', $request);
        $row = $projectReport['sections'][0]['rows'][0];
        $this->assertSame('500.00', $row[3]);
        $this->assertSame('10.25', $row[4]);
        $this->assertSame('489.75', $row[7]);
        $procurement = $service->build('procurement-report', $request);
        $this->assertSame('10.25', $procurement['sections'][0]['rows'][0][5]);
        $this->assertSame('489.75', $procurement['sections'][1]['rows'][0][2]);
        $this->actingAs($buyer)->get(route('sections.export', ['kind' => 'project-report', 'format' => 'xlsx']))->assertOk();
        $this->get(route('sections.export', ['kind' => 'procurement-report', 'format' => 'pdf']))->assertOk();
        $this->assertSame($before, Transaction::count());
        $restricted = $this->staff(['procurement.view']);
        $request->setUserResolver(fn () => $restricted);
        $this->assertSame(1, $service->build('procurement', $request)['count']);
        $this->actingAs($restricted)->get(route('sections.export', ['kind' => 'procurement-report', 'format' => 'pdf']))->assertForbidden();
    }
}
