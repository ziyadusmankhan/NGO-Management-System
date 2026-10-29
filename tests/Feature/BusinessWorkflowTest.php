<?php

namespace Tests\Feature;

use App\Domain\Business\Services\BusinessPaymentService;
use App\Domain\Business\Services\BusinessWorkflowService;
use App\Domain\Finance\Funds\Services\FundBalanceService;
use App\Domain\Finance\Funds\Services\FundReportingService;
use App\Domain\Finance\Transactions\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreatesBusinessFixtures;
use Tests\TestCase;

class BusinessWorkflowTest extends TestCase
{
    use CreatesBusinessFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
        $this->travelTo(now()->setDate(2026, 10, 23)->setTime(12, 0));
    }

    public function test_staged_funding_and_recovery_with_real_pages(): void
    {
        $officer = $this->businessActor();
        $a = $this->signedBusiness($officer);
        $treasurer = $this->businessActor();
        $this->assertSame('500.00', app(FundReportingService::class)->summary($a->fund)['allocated']);
        $this->actingAs($treasurer);
        foreach (['business.applications.index', 'business.applications.create', 'business.categories', 'business.reports'] as $route) {
            $this->get(route($route))->assertOk();
        }
        $this->get(route('business.applications.show', $a))->assertOk();
        $this->get(route('business.applications.agreement', $a))->assertOk();
        $p = $this->businessPayment($a, 'disbursement', '10.25');
        $this->post(route('business.applications.disburse', $a), $p)->assertRedirect()->assertSessionHasNoErrors();
        $this->post(route('business.applications.disburse', $a), $p)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(48975, $a->fresh()->remainingMinor());
        $this->assertDatabaseCount('transactions', 1);
        $this->postJson(route('business.applications.disburse', $a), $this->businessPayment($a, 'disbursement'))->assertUnprocessable()->assertJsonValidationErrors('amount');
        $this->post(route('business.applications.disburse', $a), $this->businessPayment($a, 'disbursement', '489.75'))->assertRedirect()->assertSessionHasNoErrors();
        $this->post(route('business.applications.finalize', $a), $this->finalization($a))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('finalized', $a->fresh()->funding_status);
        $this->assertSame(['166.66', '166.66', '166.68'], $a->installments()->pluck('amount')->all());
        $repay = $this->businessPayment($a, 'repayment', '10.25');
        $this->post(route('business.applications.repay', $a), $repay)->assertRedirect()->assertSessionHasNoErrors();
        $this->post(route('business.applications.repay', $a), $repay)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(48975, $a->fresh()->outstandingMinor());
        $this->postJson(route('business.applications.repay', $a), $this->businessPayment($a, 'repayment'))->assertUnprocessable()->assertJsonValidationErrors('amount');
        $this->post(route('business.applications.repay', $a), $this->businessPayment($a, 'repayment', '489.75'))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('Repaid', $a->fresh()->recoveryStatus());
        $this->assertSame('1000.00', app(FundBalanceService::class)->balance($a->fund));
        $this->assertSame('500.00', app(FundReportingService::class)->summary($a->fund)['recovered']);
        $this->assertDatabaseCount('transactions', 4);
        $this->get(route('business.applications.show', $a))->assertOk()->assertSee('Repaid');
        $this->get(route('business.reports', ['format' => 'print']))->assertOk();
        $this->get(route('business.reports', ['format' => 'xlsx']))->assertOk()->assertDownload();
    }

    public function test_funding_can_finish_below_cap_and_grants_never_create_debt(): void
    {
        $officer = $this->businessActor();
        $payer = $this->businessActor();
        $a = $this->signedBusiness($officer, 'grant');
        $payments = app(BusinessPaymentService::class);
        $payments->record($a, 'disbursement', $this->businessPayment($a, 'disbursement', '10.25'), $payer);
        $this->assertSame('489.75', app(FundReportingService::class)->summary($a->fund)['allocated']);
        $payments->finalize($a->fresh(), $this->finalization($a), $payer);
        $this->assertSame('0.00', app(FundReportingService::class)->summary($a->fund)['allocated']);
        $this->assertSame(0, $a->fresh()->outstandingMinor());
        $this->assertDatabaseCount('business_installments', 0);
        $this->actingAs($payer)->postJson(route('business.applications.repay', $a), $this->businessPayment($a, 'repayment', '1.00'))->assertUnprocessable();
    }

    public function test_records_report_counts_actual_business_starts(): void
    {
        $officer = $this->businessActor();
        $application = $this->fundedBusiness($officer);
        $monitor = app(\App\Domain\Business\Services\BusinessMonitoringService::class);
        $monitor->record($application, 'start', $this->versioned($application, ['started_on' => now()->toDateString(), 'evidence' => $this->evidence()]), $officer);
        $builder = app(\App\Domain\Reports\Services\ReportBuilder::class);
        $this->assertSame(1, collect($builder->build('business', [])['metrics'])->firstWhere('label', 'Businesses started')['value']);
        $monitor->record($application, 'monitor', $this->versioned($application, ['review_date' => now()->toDateString(), 'condition' => 'paused', 'findings' => 'Temporarily paused', 'actions' => 'Arrange a follow-up visit', 'final_report' => false, 'next_review_date' => now()->addWeek()->toDateString()]), $officer);
        $this->draftBusiness($officer);
        $this->assertSame(1, collect($builder->build('business', [])['metrics'])->firstWhere('label', 'Businesses started')['value']);
    }

    public function test_creator_and_previous_decision_maker_cannot_approve_or_pay(): void
    {
        $actor = $this->businessActor();
        $a = $this->draftBusiness($actor);
        $w = app(BusinessWorkflowService::class);
        $w->submit($a, $this->versioned($a), $actor);
        $w->verify($a, $this->versioned($a, ['notes' => 'Verified', 'evidence' => $this->evidence()]), $actor);
        $this->actingAs($actor)->postJson(route('business.applications.decide', [$a, 'implementation']), $this->versioned($a, ['decision' => 'accept', 'notes' => 'Yes']))->assertUnprocessable()->assertJsonValidationErrors('actor');
        $reviewer = $this->businessActor();
        $w->decide($a, 'implementation', $this->versioned($a, ['decision' => 'accept', 'notes' => 'Yes']), $reviewer);
        $this->actingAs($reviewer)->postJson(route('business.applications.decide', [$a, 'secretary']), $this->versioned($a, ['decision' => 'accept', 'notes' => 'Yes']))->assertUnprocessable()->assertJsonValidationErrors('actor');
        $signed = $this->signedBusiness($actor);
        $this->actingAs($actor)->postJson(route('business.applications.disburse', $signed), $this->businessPayment($signed, 'disbursement'))->assertUnprocessable()->assertJsonValidationErrors('actor');
    }

    public function test_private_documents_and_manage_permission_do_not_grant_money_actions(): void
    {
        $actor = $this->businessActor();
        $a = $this->signedBusiness($actor);
        $document = $a->documents()->first();
        $viewer = $this->staff(['business.view', 'business.manage']);
        $this->actingAs($viewer);
        $this->get(route('business.applications.documents.show', [$a, $document]))->assertOk();
        $this->postJson(route('business.applications.disburse', $a), $this->businessPayment($a, 'disbursement'))->assertForbidden();
        $this->actingAs($this->staff([]))->get(route('business.applications.show', $a))->assertForbidden();
        $this->get(route('business.applications.documents.show', [$a, $document]))->assertForbidden();
    }

    public function test_voids_restore_principal_and_invalidate_finalization_without_erasing_history(): void
    {
        $actor = $this->businessActor();
        $a = $this->fundedBusiness($actor);
        $payer = $this->businessActor();
        $p = app(BusinessPaymentService::class);
        $repay = $p->record($a, 'repayment', $this->businessPayment($a, 'repayment'), $payer);
        $p->void($a, $repay, $this->versioned($a, ['reason' => 'Wrong receipt']), $actor);
        $p->void($a, $repay, $this->versioned($a, ['reason' => 'Retry']), $actor);
        $this->assertSame(50000, $a->fresh()->outstandingMinor());
        $debit = $a->movements()->where('kind', 'disbursement')->first();
        $p->void($a, $debit, $this->versioned($a, ['reason' => 'Incorrect payment']), $actor);
        $this->assertSame('open',$a->fresh()->funding_status);
        $this->assertSame('1000.00',app(FundBalanceService::class)->balance($a->fund));
        $this->assertSame('500.00',app(FundReportingService::class)->summary($a->fund)['allocated']);
        $this->assertDatabaseCount('business_agreements',2);
        $this->assertDatabaseCount('business_installments',3);
        $this->assertSame(2,Transaction::where('status','void')->count());
    }
}
