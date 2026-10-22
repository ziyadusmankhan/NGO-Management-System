<?php

namespace Tests\Feature;

use App\Domain\Finance\Funds\Services\FundReportingService;
use App\Domain\Welfare\Assistance\Models\WelfareAssistance;
use App\Domain\Welfare\Assistance\Models\WelfareAssistanceReminder;
use App\Domain\Welfare\Assistance\Services\WelfareAssistancePaymentService;
use App\Domain\Welfare\Assistance\Services\WelfareAssistanceService;
use App\Domain\Welfare\Assistance\Services\WelfareMonitoringService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesWelfareFixtures;
use Tests\TestCase;

class WelfareMonitoringTest extends TestCase
{
    use CreatesWelfareFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-18 10:00:00', 'Asia/Karachi'));
        Storage::fake('private');
    }

    private function fixture(array $overrides = []): array
    {
        $actor = $this->staff();
        $application = $this->signedApplication($actor);
        $application->fund->update(['opening_balance' => '1200.00']);
        $assistance = $this->legacyAssistance($application, 'approved');
        $assistance->update(array_merge(['amount' => '500.00', 'frequency' => 'monthly', 'next_review_date' => '2026-10-18'], $overrides));

        return [$actor, $assistance->fresh(), app(WelfareMonitoringService::class)];
    }

    private function reviewData(WelfareAssistance $assistance, array $overrides = []): array
    {
        return array_merge(['idempotency_key' => (string) Str::uuid(),
            'expected_review_id' => $assistance->latestReview()->first()?->id ?? 0,
            'review_date' => '2026-10-18', 'outcome' => 'continued', 'next_review_date' => '2026-11-18',
            'notes' => 'Support is still needed after discussion with the beneficiary.', 'follow_up_action' => null], $overrides);
    }

    private function pay(WelfareAssistance $assistance, $actor, string $amount = '10.25')
    {
        return app(WelfareAssistancePaymentService::class)->record($assistance, [
            'amount' => $amount, 'payment_date' => '2026-10-18', 'payment_method' => 'cash',
            'idempotency_key' => (string) Str::uuid(),
        ], $actor)['payment'];
    }

    public function test_review_records_actor_findings_and_action_then_resolves_follow_up_with_a_later_review(): void
    {
        [$actor, $assistance, $service] = $this->fixture();
        $service->refreshReminders($assistance);
        $data = $this->reviewData($assistance, ['outcome' => 'follow_up_required', 'follow_up_action' => 'Officer to collect the updated school invoice.']);
        $review = $service->recordReview($assistance, $data, $actor);
        $this->assertSame($actor->id, $review->reviewed_by);
        $this->assertSame($data['notes'], $review->notes);
        $this->assertSame($data['follow_up_action'], $review->follow_up_action);
        $this->assertNotNull($review->created_at);
        $this->assertSame('2026-11-18', $assistance->fresh()->next_review_date->toDateString());
        $this->assertNotNull(WelfareAssistanceReminder::sole()->resolved_at);
        $this->actingAs($actor)->get(route('welfare.assistance.reviews.index', ['filter' => 'follow_up']))
            ->assertOk()->assertSee($assistance->assistance_number)->assertSee('updated school invoice');
        $continued = $service->recordReview($assistance, $this->reviewData($assistance, ['notes' => 'Invoice received; continue support.']), $actor);
        $this->assertSame($continued->id, $assistance->fresh()->latestReview->id);
        $this->get(route('welfare.assistance.reviews.index', ['filter' => 'follow_up']))->assertOk()->assertDontSee($assistance->assistance_number);
        $this->assertDatabaseCount('welfare_assistance_reviews', 2);
        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('welfare_assistance_payments', 0);
    }

    public function test_review_double_submission_returns_original_and_does_not_overwrite_newer_state(): void
    {
        [$actor, $assistance, $service] = $this->fixture();
        $data = $this->reviewData($assistance);
        $first = $service->recordReview($assistance, $data, $actor);
        $this->assertSame($first->id, $service->recordReview($assistance, $data, $actor)->id);
        $service->recordReview($assistance, $this->reviewData($assistance, ['next_review_date' => '2026-12-01']), $actor);
        $this->assertSame($first->id, $service->recordReview($assistance, $data, $actor)->id);
        $this->assertSame('2026-12-01', $assistance->fresh()->next_review_date->toDateString());
        $this->assertDatabaseCount('welfare_assistance_reviews', 2);
        $this->actingAs($actor)->postJson(route('welfare.assistance.reviews.store', $assistance), array_merge($data, ['notes' => 'Changed payload']))->assertConflict();
    }

    public function test_stale_review_form_and_backdated_latest_review_are_rejected(): void
    {
        [$actor, $assistance, $service] = $this->fixture();
        $stale = $this->reviewData($assistance);
        $service->recordReview($assistance, $this->reviewData($assistance), $actor);
        $this->actingAs($actor)->postJson(route('welfare.assistance.reviews.store', $assistance), $stale)
            ->assertUnprocessable()->assertJsonValidationErrors('review');
        $this->postJson(route('welfare.assistance.reviews.store', $assistance), $this->reviewData($assistance, ['review_date' => '2026-10-17']))
            ->assertUnprocessable()->assertJsonValidationErrors('review_date');
        $this->assertDatabaseCount('welfare_assistance_reviews', 1);
    }

    #[DataProvider('invalidReviews')]
    public function test_review_validation(array $changes, string $field): void
    {
        [$actor, $assistance] = $this->fixture();
        $this->actingAs($actor)->postJson(route('welfare.assistance.reviews.store', $assistance), $this->reviewData($assistance, $changes))
            ->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->assertDatabaseCount('welfare_assistance_reviews', 0);
        $this->assertSame('2026-10-18', $assistance->fresh()->next_review_date->toDateString());
    }

    public static function invalidReviews(): array
    {
        return [
            [['review_date' => '2026-10-19'], 'review_date'],
            [['review_date' => 'not-a-date'], 'review_date'],
            [['next_review_date' => '2026-10-17'], 'next_review_date'],
            [['next_review_date' => null], 'next_review_date'],
            [['notes' => ''], 'notes'],
            [['outcome' => 'follow_up_required', 'follow_up_action' => null], 'follow_up_action'],
            [['outcome' => 'closed'], 'outcome'],
            [['idempotency_key' => 'invalid'], 'idempotency_key'],
            [['expected_review_id' => -1], 'expected_review_id'],
        ];
    }

    public function test_closure_recommendation_keeps_approval_and_allocation_until_approver_confirms(): void
    {
        [$actor, $assistance, $service] = $this->fixture();
        $reviewer = $this->staff(['welfare.assistance.view', 'welfare.reviews.view', 'welfare.reviews.manage']);
        $this->actingAs($reviewer)->post(route('welfare.assistance.reviews.store', $assistance), $this->reviewData($assistance, [
            'outcome' => 'no_longer_needed', 'next_review_date' => null, 'notes' => 'Beneficiary no longer requires support.',
        ]))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('approved', $assistance->fresh()->status);
        $this->assertNull($assistance->fresh()->next_review_date);
        $this->assertSame('500.00', app(FundReportingService::class)->summary($assistance->fund)['allocated']);
        $this->get(route('welfare.assistance.reviews.index', ['filter' => 'closure']))->assertOk()->assertSee($assistance->assistance_number);
        $this->postJson(route('welfare.assistance.close', $assistance), ['reason' => 'Not authorized'])->assertForbidden();
        $this->actingAs($actor)->post(route('welfare.assistance.close', $assistance), ['reason' => 'Confirmed: support no longer required.'])->assertRedirect();
        $closed = $assistance->fresh();
        $this->assertSame('closed', $closed->status);
        $this->assertSame($actor->id, $closed->closed_by);
        $this->assertNotNull($closed->closed_at);
        $this->assertSame('Confirmed: support no longer required.', $closed->closure_reason);
        $this->assertSame('0.00', app(FundReportingService::class)->summary($closed->fund)['allocated']);
        $this->assertSame(0, $closed->reminders()->whereNull('resolved_at')->count());
        $this->assertDatabaseHas('welfare_assistance_status_changes', ['assistance_id' => $closed->id, 'from_status' => 'approved', 'to_status' => 'closed', 'changed_by' => $actor->id]);
        $this->assertDatabaseCount('transactions', 0);
        $this->get(route('welfare.assistance.show', $closed))->assertOk()->assertSee('Confirmed: support no longer required.')->assertDontSee('Record Payment');
    }

    public function test_closure_is_idempotent_and_blocks_reviews_and_new_payments(): void
    {
        [$actor, $assistance, $service] = $this->fixture();
        $this->pay($assistance, $actor);
        $before = app(FundReportingService::class)->summary($assistance->fund)['balance'];
        $this->assertTrue($service->close($assistance, 'Period ended after partial support.', $actor));
        $this->assertFalse($service->close($assistance, 'Repeated request must not replace the reason.', $actor));
        $this->assertSame('Period ended after partial support.', $assistance->fresh()->closure_reason);
        $this->assertDatabaseCount('welfare_assistance_status_changes', 1);
        $this->assertSame($before, app(FundReportingService::class)->summary($assistance->fund)['balance']);
        $this->actingAs($actor)->postJson(route('welfare.assistance.reviews.store', $assistance), $this->reviewData($assistance))
            ->assertUnprocessable()->assertJsonValidationErrors('status');
        try {
            $this->pay($assistance, $actor);
            $this->fail('Closed assistance cannot accept a new payment.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('status', $exception->errors());
        }
        $this->assertDatabaseCount('welfare_assistance_payments', 1);
        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_voiding_payment_after_explicit_closure_cannot_reopen_assistance(): void
    {
        [$actor, $assistance, $service] = $this->fixture();
        $payment = $this->pay($assistance, $actor, '500.00');
        $this->assertSame('completed', $assistance->fresh()->status);
        $service->close($assistance, 'Final case closure confirmed.', $actor);
        app(WelfareAssistancePaymentService::class)->void($assistance, $payment, 'Correct incorrect payment evidence.', $actor);
        $this->assertSame('closed', $assistance->fresh()->status);
        $summary = app(FundReportingService::class)->summary($assistance->fund);
        $this->assertSame('1200.00', $summary['balance']);
        $this->assertSame('0.00', $summary['allocated']);
        $this->assertSame('Final case closure confirmed.', $assistance->fresh()->closure_reason);
    }

    public function test_normal_full_payment_resolves_reminders_and_void_reopens_monitoring_without_duplicate_reminders(): void
    {
        [$actor, $assistance, $service] = $this->fixture();
        $service->refreshReminders($assistance);
        $payment = $this->pay($assistance, $actor, '500.00');
        $this->assertNotNull(WelfareAssistanceReminder::sole()->resolved_at);
        app(WelfareAssistancePaymentService::class)->void($assistance, $payment, 'Wrong recipient', $actor);
        $this->assertSame('approved', $assistance->fresh()->status);
        $this->assertNull(WelfareAssistanceReminder::sole()->resolved_at);
        $this->assertDatabaseCount('welfare_assistance_reminders', 1);
    }

    public function test_permissions_and_required_reason_are_enforced(): void
    {
        [$actor, $assistance] = $this->fixture();
        $viewer = $this->staff(['welfare.assistance.view', 'welfare.reviews.view']);
        $this->actingAs($viewer)->get(route('welfare.assistance.reviews.index'))->assertOk()->assertDontSee('Record review');
        $this->get(route('welfare.assistance.reviews.create', $assistance))->assertForbidden();
        $this->postJson(route('welfare.assistance.reviews.store', $assistance), $this->reviewData($assistance))->assertForbidden();
        $this->postJson(route('welfare.assistance.close', $assistance), ['reason' => 'No permission'])->assertForbidden();
        $this->actingAs($actor)->postJson(route('welfare.assistance.close', $assistance), ['reason' => ' '])->assertUnprocessable()->assertJsonValidationErrors('reason');
        $this->actingAs($this->staff([]))->get(route('welfare.assistance.reviews.index'))->assertForbidden();
        $this->assertDatabaseCount('welfare_assistance_reviews', 0);
        $this->assertSame('approved', $assistance->fresh()->status);
    }

    public function test_pending_rejected_and_archived_assistance_cannot_be_reviewed_or_closed(): void
    {
        foreach (['pending', 'rejected'] as $status) {
            [$actor, $assistance] = $this->fixture(['status' => $status]);
            $this->actingAs($actor)->postJson(route('welfare.assistance.close', $assistance), ['reason' => 'Invalid transition'])->assertUnprocessable();
            $this->postJson(route('welfare.assistance.reviews.store', $assistance), $this->reviewData($assistance))->assertUnprocessable();
        }
        $assistance->delete();
        $this->postJson(route('welfare.assistance.close', $assistance), ['reason' => 'Archived'])->assertNotFound();
        $this->assertDatabaseCount('welfare_assistance_reviews', 0);
        $this->assertDatabaseCount('welfare_assistance_status_changes', 0);
    }

    public function test_approval_sets_default_review_dates_and_preserves_explicit_dates(): void
    {
        foreach (['weekly' => '2026-10-25', 'monthly' => '2026-11-18', 'one_time' => '2026-11-17'] as $frequency => $expected) {
            [$actor, $assistance] = $this->fixture(['status' => 'pending', 'frequency' => $frequency, 'next_review_date' => null]);
            app(WelfareAssistanceService::class)->transition($assistance, 'approved', $actor);
            $this->assertSame($expected, $assistance->fresh()->next_review_date->toDateString());
        }
        [$actor, $assistance] = $this->fixture(['status' => 'pending', 'next_review_date' => '2026-10-20']);
        app(WelfareAssistanceService::class)->transition($assistance, 'approved', $actor);
        $this->assertSame('2026-10-20', $assistance->fresh()->next_review_date->toDateString());
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_monthly_review_uses_calendar_month_and_respects_start_and_end_dates(): void
    {
        [$actor, $assistance, $service] = $this->fixture();
        $this->travelTo(CarbonImmutable::parse('2027-01-31 12:00:00', 'Asia/Karachi'));
        $this->assertSame('2027-02-28', $service->suggestedReviewDate($assistance));
        $assistance->update(['start_date' => '2027-03-31']);
        $this->assertSame('2027-04-30', $service->suggestedReviewDate($assistance));
        $assistance->update(['end_date' => '2027-04-15']);
        $this->assertSame('2027-04-15', $service->suggestedReviewDate($assistance));
    }

    public function test_scheduler_creates_due_and_expired_reminders_once_without_closing_or_paying(): void
    {
        [$actor, $assistance] = $this->fixture(['next_review_date' => '2026-10-17', 'end_date' => '2026-10-17']);
        foreach ([1, 2] as $run) {
            $this->assertSame(0, Artisan::call('welfare:refresh-reminders'));
        }
        $this->assertDatabaseCount('welfare_assistance_reminders', 2);
        $this->assertSame('approved', $assistance->fresh()->status);
        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('welfare_assistance_payments', 0);
        $this->assertDatabaseCount('welfare_assistance_status_changes', 0);
        $this->actingAs($actor)->get(route('welfare.assistance.reviews.index', ['filter' => 'ended']))
            ->assertOk()->assertSee($assistance->assistance_number)->assertSee('Period ended');
    }

    public function test_reminder_dates_use_karachi_day_and_end_date_is_inclusive(): void
    {
        [$actor, $assistance, $service] = $this->fixture(['next_review_date' => '2026-10-19', 'end_date' => '2026-10-18']);
        $this->travelTo(CarbonImmutable::parse('2026-10-18 18:59:00', 'UTC'));
        $service->refreshReminders($assistance);
        $this->assertDatabaseCount('welfare_assistance_reminders', 0);
        $this->travelTo(CarbonImmutable::parse('2026-10-18 19:01:00', 'UTC'));
        $service->refreshReminders($assistance);
        $this->assertDatabaseCount('welfare_assistance_reminders', 2);
        $this->assertSame('2026-10-19', $service->today()->toDateString());
    }

    public function test_scheduler_resolves_stale_archived_and_completed_reminders(): void
    {
        [$actor, $assistance, $service] = $this->fixture();
        $service->refreshReminders($assistance);
        $assistance->delete();
        $this->assertSame(0, Artisan::call('welfare:refresh-reminders'));
        $this->assertNotNull(WelfareAssistanceReminder::sole()->resolved_at);
        [$actor, $other, $service] = $this->fixture();
        $service->refreshReminders($other);
        $other->update(['status' => 'completed']);
        Artisan::call('welfare:refresh-reminders');
        $this->assertSame(0, WelfareAssistanceReminder::whereNull('resolved_at')->count());
    }

    public function test_queue_filters_and_form_render_current_state_with_no_permission_leaks(): void
    {
        [$actor, $assistance] = $this->fixture();
        $this->actingAs($actor)->get(route('welfare.assistance.reviews.create', $assistance))->assertOk()
            ->assertSee('idempotency_key', false)->assertSee('expected_review_id', false)->assertSee('2026-11-18');
        $this->get(route('welfare.assistance.reviews.index', ['filter' => 'today']))->assertOk()->assertSee($assistance->assistance_number);
        $this->get(route('welfare.assistance.reviews.index', ['filter' => 'overdue']))->assertOk()->assertDontSee($assistance->assistance_number);
        $this->get(route('welfare.assistance.reviews.index', ['search' => 'no-such-person']))->assertOk()->assertDontSee($assistance->assistance_number);
        $this->get(route('welfare.assistance.show', $assistance))->assertOk()->assertSee('Due today')->assertSee('Confirm closure');
        $this->get(route('welfare.assistance.index'))->assertOk()->assertSee('Review and follow-up queue')->assertSee('Closed');
    }

    public function test_review_and_closure_roll_back_if_reminder_or_status_audit_fails(): void
    {
        [$actor, $assistance, $service] = $this->fixture();
        DB::unprepared("CREATE TRIGGER reject_reminder BEFORE INSERT ON welfare_assistance_reminders BEGIN SELECT RAISE(ABORT, 'reminder failure'); END");
        try {
            $service->recordReview($assistance, $this->reviewData($assistance, ['next_review_date' => '2026-10-18']), $actor);
            $this->fail('Review must roll back with reminder failure.');
        } catch (\Illuminate\Database\QueryException $exception) {
            $this->assertStringContainsString('reminder failure', $exception->getMessage());
        } finally {
            DB::unprepared('DROP TRIGGER reject_reminder');
        }
        $this->assertDatabaseCount('welfare_assistance_reviews', 0);
        DB::unprepared("CREATE TRIGGER reject_closure BEFORE INSERT ON welfare_assistance_status_changes BEGIN SELECT RAISE(ABORT, 'audit failure'); END");
        try {
            $service->close($assistance, 'Must roll back', $actor);
            $this->fail('Closure must roll back with audit failure.');
        } catch (\Illuminate\Database\QueryException $exception) {
            $this->assertStringContainsString('audit failure', $exception->getMessage());
        } finally {
            DB::unprepared('DROP TRIGGER reject_closure');
        }
        $this->assertSame('approved', $assistance->fresh()->status);
        $this->assertNull($assistance->fresh()->closed_at);
        $this->assertSame('2026-10-18', $assistance->fresh()->next_review_date->toDateString());
    }

    public function test_daily_schedule_is_registered_and_monitoring_migration_is_reversible(): void
    {
        Artisan::call('schedule:list');
        $events = collect(Schedule::events())->filter(fn ($event) => str_contains($event->command ?? '', 'welfare:refresh-reminders'));
        $this->assertCount(1, $events);
        $this->assertSame('0 8 * * *', $events->first()->expression);
        $this->assertSame('Asia/Karachi', $events->first()->timezone);
        $this->assertTrue($events->first()->withoutOverlapping);
        $migration = require database_path('migrations/2026_10_18_000002_add_welfare_monitoring.php');
        $migration->down();
        $this->assertFalse(Schema::hasTable('welfare_assistance_reminders'));
        $this->assertFalse(Schema::hasColumn('welfare_assistances', 'closed_at'));
        $migration->up();
        $this->assertTrue(Schema::hasTable('welfare_assistance_reminders'));
        $this->assertTrue(Schema::hasColumn('welfare_assistance_reviews', 'idempotency_key'));
    }
}
