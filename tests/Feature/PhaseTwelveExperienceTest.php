<?php

namespace Tests\Feature;

use App\Domain\Audit\Services\AuditRecorder;
use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Members\Models\Member;
use App\Domain\Notifications\Models\NotificationState;
use App\Domain\Welfare\Assistance\Models\WelfareAssistance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreatesWelfareFixtures;
use Tests\TestCase;

class PhaseTwelveExperienceTest extends TestCase
{
    use CreatesWelfareFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
    }

    public function test_global_search_uses_source_permissions_and_exact_sensitive_lookup(): void
    {
        $member = Member::create([
            'member_number' => 'MEM-SEARCH-01',
            'first_name' => 'Searchable',
            'last_name' => 'Member',
            'cnic' => '12345-1234567-1',
            'phone' => '+92 300 1234567',
            'status' => 'active',
        ]);
        $fund = Fund::create([
            'fund_reference' => 'FND-SEARCH-01',
            'name' => 'Hidden Search Fund',
            'purpose' => 'general_welfare',
            'status' => 'active',
            'opening_balance' => '100.00',
        ]);

        $memberReader = $this->staff(['members.view']);
        $this->actingAs($memberReader)->get(route('search.index', ['q' => '12345-1234567-1']))
            ->assertOk()->assertSee($member->member_number)->assertSee('Searchable Member');
        $this->get(route('search.index', ['q' => '12345-123']))
            ->assertOk()->assertDontSee($member->member_number);
        $this->get(route('search.index', ['q' => 'Hidden Search Fund']))
            ->assertOk()->assertDontSee($fund->fund_reference)->assertSee('No authorized records found');

        $financeReader = $this->staff(['finance.view']);
        $this->actingAs($financeReader)->get(route('search.index', ['q' => 'Hidden Search Fund']))
            ->assertOk()->assertSee($fund->fund_reference)->assertSee($fund->name);
        $this->get(route('search.index', ['q' => '%%']))->assertOk()->assertSee('No authorized records found');
    }

    public function test_notification_inbox_is_permission_filtered_and_keeps_per_user_state(): void
    {
        $reader = $this->staff(['welfare.assistance.view']);
        $application = $this->signedApplication($reader);
        $assistance = $this->legacyAssistance($application, 'approved');
        $assistance->reminders()->create(['kind' => 'review_due', 'due_date' => today('Asia/Karachi')]);

        $this->actingAs($reader)->get(route('notifications.index'))
            ->assertOk()->assertSee($assistance->assistance_number)->assertSeeText('1 unread current notification');
        $outsider = $this->staff([]);
        $this->actingAs($outsider)->get(route('notifications.index'))
            ->assertOk()->assertDontSee($assistance->assistance_number)->assertSee('Nothing needs attention');

        $key = 'welfare:'.$assistance->reminders()->firstOrFail()->id;
        $this->actingAs($reader)->post(route('notifications.change'), ['key' => $key, 'action' => 'read'])->assertRedirect();
        $this->get(route('notifications.index'))->assertOk()->assertSeeText('0 unread current notifications');
        $this->post(route('notifications.change'), ['key' => $key, 'action' => 'dismiss'])->assertRedirect();
        $this->get(route('notifications.index'))->assertOk()->assertDontSee($assistance->assistance_number);
        $this->get(route('notifications.index', ['scope' => 'dismissed']))->assertOk()->assertSee($assistance->assistance_number);
        $this->assertDatabaseHas('user_notification_states', ['user_id' => $reader->id, 'notification_key' => $key]);
        $this->assertNotNull(NotificationState::where('user_id', $reader->id)->firstOrFail()->dismissed_at);
    }

    public function test_dashboard_shows_live_welfare_qard_and_authorized_recent_activity(): void
    {
        $reader = $this->staff(['welfare.assistance.view', 'qard.view']);
        $application = $this->signedApplication($reader);
        $assistance = $this->legacyAssistance($application, 'approved');
        $assistance->reminders()->create(['kind' => 'review_due', 'due_date' => today('Asia/Karachi')]);
        $this->actingAs($reader);
        app(AuditRecorder::class)->record('welfare', 'dashboard_fixture', [
            'subject_type' => WelfareAssistance::class,
            'subject_id' => $assistance->id,
            'subject_reference' => $assistance->assistance_number,
            'summary' => 'Welfare dashboard activity recorded',
        ]);

        $this->get(route('dashboard'))->assertOk()
            ->assertSee('Recent Activity')->assertSee('Welfare dashboard activity recorded')
            ->assertSee('Welfare overview')->assertSee('Qard-e-Hasana overview')
            ->assertSee('Reviews due')->assertSee($assistance->assistance_number)
            ->assertDontSee('Total Members')->assertDontSee('Total Fund Balance')
            ->assertDontSee('Recent Transactions');
    }

    public function test_phase_twelve_routes_require_authentication(): void
    {
        $this->get(route('search.index', ['q' => 'test']))->assertRedirect('/login');
        $this->get(route('notifications.index'))->assertRedirect('/login');
        $this->post(route('notifications.read-all'))->assertRedirect('/login');
    }
}
