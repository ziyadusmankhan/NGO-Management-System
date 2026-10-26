<?php

namespace Tests\Feature;

use App\Domain\Finance\Donations\Models\Donation;
use App\Domain\Finance\Funds\Services\FundBalanceService;
use App\Domain\Finance\Funds\Services\FundPurposeService;
use App\Domain\Finance\Funds\Services\FundReportingService;
use App\Domain\Finance\Transactions\Models\Transaction;
use App\Domain\Finance\Transactions\Services\TransactionService;
use App\Domain\Projects\Services\ProjectMonitoringService;
use App\Domain\Projects\Services\ProjectPaymentService;
use App\Domain\Projects\Services\ProjectReportService;
use App\Domain\Projects\Services\ProjectWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\Support\CreatesProjectFixtures;
use Tests\TestCase;

class ProjectWorkflowTest extends TestCase
{
    use CreatesProjectFixtures,RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
        $this->travelTo(now()->setDate(2026, 10, 24)->setTime(12, 0));
    }

    private function blocked(callable $callback): void
    {
        try {
            $callback();
            $this->fail('Expected validation rejection');
        } catch (ValidationException $e) {
            $this->assertNotEmpty($e->errors());
        }
    }

    public function test_approval_creates_commitment_without_payment_and_screens_render(): void
    {
        $actor = $this->projectActor();
        $p = $this->approveProject($this->draftProject($actor), $actor);
        $this->assertSame('approved', $p->status);
        $this->assertSame(0, Transaction::count());
        $this->assertSame('500.00', app(FundReportingService::class)->summary($p->fund)['allocated']);
        foreach (['projects.index', 'projects.create', 'projects.categories', 'projects.reports'] as $route) {
            $this->actingAs($actor)->get(route($route))->assertOk();
        }
        $this->get(route('projects.show', $p))->assertOk()->assertSee('Active approved budget');
        $this->get(route('finance.funds.show', $p->fund))->assertOk()->assertSee($p->number);
        $this->get(route('dashboard'))->assertOk()->assertSee('Project Status');
    }

    public function test_exact_caps_idempotency_and_refunds(): void
    {
        $officer = $this->projectActor();
        $payer = $this->projectActor();
        $p = $this->approveProject($this->draftProject($officer), $officer);
        $s = app(ProjectPaymentService::class);
        $d = $this->projectPayment($p, '10.25');
        $m = $s->record($p, 'payment', $d, $payer);
        $this->assertSame($m->id, $s->record($p, 'payment', $d, $payer)->id);
        $this->assertSame(48975, $p->fresh()->remainingMinor());
        $this->blocked(fn () => $s->record($p, 'payment', $this->projectPayment($p, '500'), $payer));
        $s->record($p, 'payment', $this->projectPayment($p, '489.75'), $payer);
        $this->assertSame(0, $p->fresh()->remainingMinor());
        $this->assertSame(2, Transaction::count());
        $this->assertSame('500.00', app(FundBalanceService::class)->balance($p->fund));
        $last = $p->movements()->latest('id')->first();
        $refund = $s->record($p, 'refund', $this->refundData($p, $last), $payer);
        $this->assertSame(10000, $p->fresh()->remainingMinor());
        $this->assertSame($last->budget_item_id, $refund->budget_item_id);
        $this->assertSame($p->fund_id, $refund->transaction->fund_id);
        $this->assertSame('100.00', app(FundReportingService::class)->summary($p->fund)['refunded']);
        $this->blocked(fn () => $s->record($p, 'refund', $this->refundData($p, $last, '400'), $payer));
        $this->actingAs($payer)->get(route('finance.expenses.index'))->assertOk()->assertSee($p->number);
        $this->get(route('finance.transactions.show', $refund->transaction))->assertOk()->assertSee(route('projects.show', $p));
        $this->get(route('finance.funds.show', $p->fund))->assertOk();
    }

    public function test_skipped_stages_creator_decisions_and_payer_separation(): void
    {
        $a = $this->projectActor();
        $p = $this->draftProject($a);
        $w = app(ProjectWorkflowService::class);
        $d = ['decision' => 'accept', 'notes' => 'Yes'];
        $this->blocked(fn () => $w->decide($p, 'secretary', $this->projectVersion($p, $d), $this->projectActor()));
        $w->submit($p, $this->projectVersion($p), $a);
        $w->verify($p, $this->projectVersion($p, ['notes' => 'Found', 'evidence' => $this->evidence()]), $a);
        $this->blocked(fn () => $w->decide($p, 'coordinator', $this->projectVersion($p, $d), $a));
        $other = $this->projectActor();
        $w->decide($p, 'coordinator', $this->projectVersion($p, $d), $other);
        $this->blocked(fn () => $w->decide($p, 'secretary', $this->projectVersion($p, $d), $other));
        $w->decide($p, 'secretary', $this->projectVersion($p, $d), $this->projectActor());
        $consent = $this->committeeResolution();
        $consent['evidence'] = $consent['resolution_file'];
        $bad = $consent;
        $bad['consenting_member_ids'] = [$consent['consenting_member_ids'][0]];
        $this->blocked(fn () => $w->decide($p, 'committee', $this->projectVersion($p, $d + $bad), $this->projectActor()));
        $w->decide($p, 'committee', $this->projectVersion($p, $d + $consent), $this->projectActor());
        foreach ([$a, $other] as $payer) {
            $this->blocked(fn () => app(ProjectPaymentService::class)->record($p, 'payment', $this->projectPayment($p), $payer));
        }
    }

    public function test_amendments_freeze_preserve_budget_and_require_explicit_resume(): void
    {
        $a = $this->projectActor();
        $p = $this->approveProject($this->draftProject($a), $a);
        $w = app(ProjectWorkflowService::class);
        $payer = $this->projectActor();
        app(ProjectPaymentService::class)->record($p, 'payment', $this->projectPayment($p, '200'), $payer);
        $w->transition($p, 'amend', $this->projectVersion($p, ['reason' => 'Revise scope']), $a);
        $p = $p->fresh();
        $this->assertTrue($p->payments_frozen);
        $this->assertSame('300.00', app(FundReportingService::class)->summary($p->fund)['allocated']);
        $this->blocked(fn () => app(ProjectPaymentService::class)->record($p, 'payment', $this->projectPayment($p, '10'), $payer));
        $this->blocked(fn () => $w->save($p, $this->projectRevisionData($p, '199'), $a));
        $w->save($p, $this->projectRevisionData($p, '700'), $a);
        $this->assertSame('500.00', $p->approved()->budget);
        $this->assertSame(2, $p->revisions()->count());
        $w->transition($p, 'withdraw', $this->projectVersion($p, ['reason' => 'Keep old plan']), $a);
        $this->assertTrue($p->fresh()->payments_frozen);
        $w->transition($p, 'resume', $this->projectVersion($p, ['reason' => 'Resume approved scope']), $a);
        $this->assertFalse($p->fresh()->payments_frozen);
        app(ProjectPaymentService::class)->record($p, 'payment', $this->projectPayment($p, '300'), $payer);
    }

    public function test_amendment_approval_replaces_limits_without_ledger_movement(): void
    {
        $a = $this->projectActor();
        $p = $this->approveProject($this->draftProject($a), $a);
        $w = app(ProjectWorkflowService::class);
        $w->transition($p, 'amend', $this->projectVersion($p, ['reason' => 'More work']), $a);
        $p = $p->fresh();
        $w->save($p, $this->projectRevisionData($p, '700'), $a);
        $p = $this->approveProject($p, $a);
        $this->assertSame(2, $p->approved_revision);
        $this->assertSame('700.00', $p->approved()->budget);
        $this->assertSame(0, Transaction::count());
    }

    public function test_complete_final_bills_close_late_refund_and_reopen(): void
    {
        $a = $this->projectActor();
        $payer = $this->projectActor();
        $closer = $this->projectActor();
        $p = $this->approveProject($this->draftProject($a), $a);
        $w = app(ProjectWorkflowService::class);
        $monitor = app(ProjectMonitoringService::class);
        $s = app(ProjectPaymentService::class);
        $monitor->record($p, 'start', $this->projectVersion($p, ['date' => now()->toDateString(), 'evidence' => $this->evidence()]), $a);
        $monitor->record($p, 'complete', $this->projectVersion($p, ['date' => now()->toDateString(), 'percent' => 100, 'findings' => 'Work completed', 'evidence' => $this->evidence()]), $a);
        $m = $s->record($p, 'payment', $this->projectPayment($p, '400'), $payer);
        $this->assertSame('completed', $p->fresh()->status);
        $this->blocked(fn () => $w->transition($p, 'close', $this->projectVersion($p, ['reason' => 'Done', 'final_bills_settled' => 1]), $a));
        $w->transition($p, 'close', $this->projectVersion($p, ['reason' => 'Final bills reviewed', 'final_bills_settled' => 1]), $closer);
        $this->assertSame(0, $p->fresh()->commitmentMinor());
        $this->blocked(fn () => $s->record($p, 'payment', $this->projectPayment($p, '100'), $payer));
        $s->record($p, 'refund', $this->refundData($p, $m), $payer);
        $this->assertSame('closed', $p->fresh()->status);
        $w->transition($p, 'reopen', $this->projectVersion($p, ['reason' => 'Additional approved work']), $closer);
        $p = $p->fresh();
        $w->save($p, $this->projectRevisionData($p, '600'), $a);
        $p = $this->approveProject($p, $a);
        $this->assertSame('in_progress', $p->status);
        $this->assertNull($p->completed_on);
        $this->assertSame(30000, $p->remainingMinor());
    }

    public function test_void_order_budget_restoration_and_revoid(): void
    {
        $a = $this->projectActor();
        $payer = $this->projectActor();
        $p = $this->approveProject($this->draftProject($a), $a);
        $s = app(ProjectPaymentService::class);
        $m = $s->record($p, 'payment', $this->projectPayment($p), $payer);
        $r = $s->record($p, 'refund', $this->refundData($p, $m), $payer);
        $this->blocked(fn () => $s->void($p, $m, $this->projectVersion($p, ['reason' => 'Error']), $payer));
        $s->void($p, $r, $this->projectVersion($p, ['reason' => 'Refund was not received']), $payer);
        $s->void($p, $r, $this->projectVersion($p, ['reason' => 'Same correction']), $payer);
        $s->void($p, $m, $this->projectVersion($p, ['reason' => 'Payment was not made']), $payer);
        $this->assertSame(50000, $p->fresh()->remainingMinor());
        $this->assertSame('1000.00', app(FundBalanceService::class)->balance($p->fund));
        $this->assertSame(2, Transaction::where('status', 'void')->count());
    }

    public function test_dedicated_fund_blocks_other_modules_and_donation_locks_original_fund(): void
    {
        $a = $this->projectActor();
        $data = $this->projectData($a);
        $data['fund_id'] = null;
        $w = app(ProjectWorkflowService::class);
        $p = $w->save(null, $data, $a);
        $w->dedicatedFund($p, $this->projectVersion($p, ['name' => 'Named project fund']), $a);
        $p = $p->fresh();
        $f = $p->fund;
        $purpose = app(FundPurposeService::class);
        $this->assertFalse($purpose->allowsQard($f));
        $this->assertFalse($purpose->allowsBusiness($f, 'repayable'));
        $this->assertFalse($purpose->allowsExpense($f, 'project'));
        $other = $this->draftProject($a);
        $this->assertFalse($purpose->allowsProject($f, $other, 'community_development'));
        $donation = Donation::create(['donation_number' => 'DON-PRJ', 'donor_name' => 'Private donor', 'donor_type' => 'individual', 'amount' => '100', 'donation_date' => now()->toDateString(), 'payment_method' => 'cash', 'designation' => 'restricted', 'purpose' => 'project', 'fund_id' => $f->id, 'status' => 'received', 'recorded_by' => $a->id]);
        app(TransactionService::class)->createForSource($donation, ['fund_id' => $f->id, 'type' => 'donation', 'direction' => 'credit', 'amount' => '100', 'transaction_date' => now()->toDateString(), 'description' => 'Project donation']);
        $this->assertNotNull($p->fresh()->fund_locked_at);
        $changed = $data;
        $changed['fund_id'] = $other->fund_id;
        $changed['version'] = $p->fresh()->version;
        $this->blocked(fn () => $w->save($p, $changed, $a));
        $reader = $this->staff(['projects.view']);
        $this->actingAs($reader)->get(route('projects.show', $p))->assertOk()->assertDontSee('Private donor');
    }

    public function test_access_private_files_stale_versions_and_exports(): void
    {
        $a = $this->projectActor();
        $p = $this->approveProject($this->draftProject($a), $a);
        $payer = $this->projectActor();
        $s = app(ProjectPaymentService::class);
        $d = $this->projectPayment($p, '10.25');
        $s->record($p, 'payment', $d, $payer);
        $d['idempotency_key'] = (string) Str::uuid();
        $this->blocked(fn () => $s->record($p, 'payment', $d, $payer));
        $reader = $this->staff(['projects.view']);
        $this->actingAs($reader)->post(route('projects.pay', $p), $this->projectPayment($p))->assertForbidden();
        $this->get(route('projects.reports'))->assertForbidden();
        $doc = $p->documents()->first();
        $this->get(route('projects.documents.show', [$p, $doc]))->assertOk();
        $other = $this->draftProject($a);
        $this->get(route('projects.documents.show', [$other, $doc]))->assertNotFound();
        $this->actingAs($a)->get(route('projects.reports', ['format' => 'print', 'from' => '2026-10-24', 'to' => '2026-10-24']))->assertOk()->assertSee('10.25');
        $this->get(route('projects.reports', ['to' => '2026-10-24']))->assertOk()->assertSee('10.25');
        $response = $this->get(route('projects.reports', ['format' => 'xlsx']))->assertOk();
        $content = $response->streamedContent();
        $this->assertStringStartsWith('PK', $content);
        $exportPath = tempnam(sys_get_temp_dir(), 'project-export-');
        try {
            file_put_contents($exportPath, $content);
            $book = \PhpOffice\PhpSpreadsheet\IOFactory::load($exportPath);
            $this->assertSame('10.25', (string) $book->getActiveSheet()->getCell('I2')->getValue());
            $this->assertSame('10.25', (string) $book->getActiveSheet()->getCell('I4')->getValue());
            $this->assertSame('489.75', (string) $book->getActiveSheet()->getCell('L2')->getValue());
            $book->disconnectWorksheets();
        } finally {
            unlink($exportPath);
        }
        $row = app(ProjectReportService::class)->rows(['from' => '2026-10-24', 'to' => '2026-10-24'])->first();
        $this->assertSame('10.25',$row['gross']);
    }

    public function test_reminders_month_end_and_no_payments(): void
    {
        $this->travelTo(now()->setDate(2026,1,31));
        $a = $this->projectActor();
        $p = $this->approveProject($this->draftProject($a),$a);
        $s = app(ProjectMonitoringService::class);
        $s->record($p,'start',$this->projectVersion($p,['date' => '2026-01-31', 'evidence' => $this->evidence()]),$a);
        $this->assertSame('2026-02-28',$p->fresh()->next_review_date->toDateString());
        $this->travelTo(now()->setDate(2026,3,4));
        $this->artisan('projects:refresh-reminders')->assertSuccessful();
        $this->assertTrue($p->fresh()->reminder->review_due);
        $this->assertTrue($p->fresh()->reminder->completion_overdue);
        $this->assertSame(0,Transaction::count());
    }
}
