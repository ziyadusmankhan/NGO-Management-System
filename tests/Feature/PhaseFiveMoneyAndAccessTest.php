<?php

namespace Tests\Feature;

use App\Domain\Finance\Expenses\Models\Expense;
use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Finance\Reports\Services\FinanceReportService;
use App\Domain\Finance\Support\Money;
use App\Domain\Finance\Transactions\Models\Transaction;
use App\Domain\Users\Models\Role;
use App\Domain\Welfare\Applications\Models\WelfareApplication;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\WelfareReviewRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesWelfareFixtures;
use Tests\TestCase;

class PhaseFiveMoneyAndAccessTest extends TestCase
{
    use CreatesWelfareFixtures, RefreshDatabase;

    public function test_report_dashboard_and_ledger_keep_cents_in_totals_and_empty_results(): void
    {
        $actor = $this->staff(['finance.view', 'finance.donations.manage', 'finance.reports.view', 'finance.expenses.manage']);
        $fund = Fund::create(['fund_reference' => 'FND-EXACT', 'name' => 'Exact fund', 'type' => 'general',
            'purpose' => 'general_welfare', 'status' => 'active', 'opening_balance' => '1000.00']);
        $this->actingAs($actor);
        foreach (['10.25', '0.10', '0.20'] as $amount) {
            $this->postJson(route('finance.donations.store'), $this->donationData($fund, $amount))->assertRedirect();
        }
        $overview = app(FinanceReportService::class)->overview($fund->id);
        $this->assertSame('10.55', $overview['total_credits']);
        $this->assertSame('10.55', $overview['net_movement']);
        $this->assertSame('1010.55', $overview['current_balance']);
        $this->assertSame('0.00', $overview['total_debits']);
        $this->get(route('dashboard'))->assertOk()->assertViewHas('totalFundBalance', '1010.55')->assertSee('1,010.55');
        $this->get(route('finance.transactions.index'))->assertOk()->assertViewHas('totalCredits', '10.55')->assertSee('10.55');
        $this->get(route('finance.donations.index'))->assertOk()->assertViewHas('donationsThisMonth', '10.55')->assertSee('10.55');
        $this->get(route('finance.expenses.index'))->assertOk()->assertViewHas('thisMonth', '0.00');
        $this->get(route('finance.reports.index'))->assertOk()->assertSee('1,010.55');
        $this->assertSame('0.00', app(FinanceReportService::class)->overview($fund->id, '2099-01-01')['net_movement']);
    }

    public function test_large_database_amounts_and_total_are_exact_decimal_text(): void
    {
        $fund = Fund::create(['fund_reference' => 'FND-LARGE', 'name' => 'Large fund', 'type' => 'general',
            'purpose' => 'general_welfare', 'status' => 'active', 'opening_balance' => '999999999999.99']);
        foreach (['999999999999.99', '0.01', '0.10', '0.20'] as $index => $amount) {
            Transaction::create(['transaction_number' => 'TX-'.$index, 'fund_id' => $fund->id,
                'type' => 'donation', 'direction' => 'credit', 'description' => 'Exact amount',
                'amount' => $amount, 'transaction_date' => today(), 'status' => 'posted']);
        }
        $this->assertSame('1000000000000.30', Money::sum(Transaction::query()));
        $overview = app(FinanceReportService::class)->overview();
        $this->assertSame('2000000000000.29', $overview['current_balance']);
        $this->assertSame('2,000,000,000,000.29', Money::format($overview['current_balance']));
        $expense = Expense::create(['expense_number' => 'EXP-LARGE', 'fund_id' => $fund->id,
            'category' => 'welfare', 'description' => 'Large amount', 'amount' => '999999999999.99',
            'expense_date' => today(), 'payee_name' => 'Example', 'payment_method' => 'cash', 'status' => 'pending']);
        $this->assertSame('999999999999.99', $expense->fresh()->amount);
        $this->assertSame('-0.01', Money::total(['0.10', '-0.11']));
        $this->assertSame('0.00', Money::total([]));
    }

    #[DataProvider('invalidAmounts')]
    public function test_donation_and_expense_requests_reject_lossy_money(mixed $amount): void
    {
        $actor = $this->staff(['finance.view', 'finance.donations.manage', 'finance.expenses.manage']);
        $fund = Fund::create(['fund_reference' => 'FND-VALIDATE', 'name' => 'Validation fund',
            'type' => 'general', 'purpose' => 'general_welfare', 'status' => 'active', 'opening_balance' => '1000.00']);
        $this->actingAs($actor)->postJson(route('finance.donations.store'), $this->donationData($fund, $amount))
            ->assertUnprocessable()->assertJsonValidationErrors('amount');
        $this->postJson(route('finance.expenses.store'), [
            'fund_id' => $fund->id, 'category' => 'welfare', 'description' => 'Test expense', 'amount' => $amount,
            'expense_date' => today()->toDateString(), 'payee_name' => 'Example', 'payment_method' => 'cash', 'status' => 'pending',
        ])->assertUnprocessable()->assertJsonValidationErrors('amount');
        $this->assertDatabaseCount('donations', 0);
        $this->assertDatabaseCount('expenses', 0);
        $this->assertDatabaseCount('transactions', 0);
    }

    public static function invalidAmounts(): array
    {
        return [['0.005'], ['10.255'], ['1e2'], ['1000000000000.00'], [10.25], ['-0.01'], ['0.00']];
    }

    public function test_review_role_is_idempotent_and_has_no_payment_or_closure_authority(): void
    {
        Storage::fake('private');
        $this->seed(WelfareReviewRoleSeeder::class);
        $this->seed(WelfareReviewRoleSeeder::class);
        $role = Role::where('slug', 'welfare-review-officer')->sole();
        $this->assertCount(4, $role->permissions);
        $actor = $this->staff([]);
        $actor->roles()->sync([$role->id]);
        $application = $this->signedApplication($actor);
        $assistance = $this->legacyAssistance($application, 'approved');
        $this->actingAs($actor)->get(route('welfare.assistance.reviews.index'))->assertOk();
        $this->get(route('welfare.assistance.reviews.create', $assistance))->assertOk();
        $this->get(route('welfare.assistance.show', $assistance))->assertOk()->assertDontSee('Record Payment')->assertDontSee('Confirm closure');
        $this->get(route('welfare.assistance.payments.create', $assistance))->assertForbidden();
        $this->postJson(route('welfare.assistance.close', $assistance), ['reason' => 'Not authorized'])->assertForbidden();
        $this->postJson(route('welfare.assistance.reviews.store', $assistance), [
            'idempotency_key' => (string) Str::uuid(), 'expected_review_id' => 0,
            'review_date' => today()->toDateString(), 'next_review_date' => today()->addWeek()->toDateString(),
            'outcome' => 'follow_up_required', 'notes' => 'Private review findings', 'follow_up_action' => 'Contact beneficiary',
        ])->assertRedirect();
        $this->assertDatabaseCount('welfare_assistance_reviews', 1);
        $this->assertDatabaseCount('transactions', 0);
        $legacyManager = $this->staff(['welfare.assistance.view', 'welfare.assistance.manage', 'welfare.assistance.approve']);
        $this->actingAs($legacyManager)->get(route('welfare.assistance.reviews.index'))->assertForbidden();
        $this->get(route('welfare.assistance.show', $assistance))->assertOk()->assertDontSee('Private review findings')->assertDontSee('Record Review');
        $this->postJson(route('welfare.assistance.close', $assistance), ['reason' => 'Missing review permission'])->assertForbidden();
    }

    public function test_separate_closure_permission_does_not_grant_payment_management(): void
    {
        Storage::fake('private');
        $actor = $this->staff(['welfare.assistance.view', 'welfare.reviews.view', 'welfare.reviews.close']);
        $assistance = $this->legacyAssistance($this->signedApplication($actor), 'approved');
        $this->actingAs($actor)->postJson(route('welfare.assistance.close', $assistance), ['reason' => 'Support no longer needed'])->assertRedirect();
        $this->assertSame('closed', $assistance->fresh()->status);
        $this->get(route('welfare.assistance.payments.create', $assistance))->assertForbidden();
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_operations_and_records_pages_are_permission_protected_and_do_not_show_sample_records(): void
    {
        $this->actingAs($this->staff());
        foreach (['/operations/tasks', '/records/reports'] as $url) {
            $this->get($url)->assertForbidden();
        }

        $authorized = $this->staff(['tasks.view', 'reports.view']);
        $this->actingAs($authorized)->get('/operations/tasks')->assertOk()
            ->assertSee('No tasks match these filters.')
            ->assertDontSee('Ahmed Khan')
            ->assertDontSee('2.84M');
        $this->get('/records/reports')->assertOk()
            ->assertSee('No reports have been generated yet.')
            ->assertDontSee('Ahmed Khan')
            ->assertDontSee('2.84M');
    }

    public function test_empty_application_pages_render_for_a_single_administrator(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = $this->staff([]);
        $admin->roles()->sync([Role::where('slug', 'administrator')->sole()->id]);
        $this->actingAs($admin);
        foreach ([
            'dashboard', 'users.index', 'users.roles', 'members.index', 'committees.index',
            'finance.funds.index', 'finance.funds.create', 'finance.donations.index', 'finance.donations.create',
            'finance.expenses.index', 'finance.expenses.create', 'finance.transactions.index',
            'finance.restricted-funds.index', 'finance.reports.index', 'welfare.beneficiaries.index',
            'welfare.applications.index', 'welfare.applications.create', 'welfare.assistance.index',
            'welfare.assistance.reviews.index',
        ] as $route) {
            $this->get(route($route))->assertOk();
        }
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('funds', 0);
        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('welfare_applications', 0);
    }

    public function test_application_contributions_are_subtracted_exactly_and_client_total_is_ignored(): void
    {
        Storage::fake('private');
        $actor = $this->staff();
        $existing = $this->signedApplication($actor);
        $data = [
            'beneficiary_id' => $existing->beneficiary_id, 'fund_id' => $existing->fund_id,
            'category' => 'education', 'request_type' => 'education_support', 'requested_amount' => '500.00',
            'applicant_contribution' => '10.25', 'organization_contribution' => '999.99',
            'purpose_details' => 'School tuition assistance for this term', 'priority' => 'normal', 'frequency' => 'one_time',
        ];
        $this->actingAs($actor)->postJson(route('welfare.applications.store'), $data)->assertRedirect();
        $application = WelfareApplication::latest('id')->first();
        $this->assertSame('489.75', $application->organization_contribution);
        $this->assertSame('10.25', $application->applicant_contribution);
        $data['requested_amount'] = '0.30';
        $data['applicant_contribution'] = '0.10';
        $this->postJson(route('welfare.applications.store'), $data)->assertRedirect();
        $this->assertSame('0.20', WelfareApplication::latest('id')->first()->organization_contribution);
        $data['applicant_contribution'] = '0.105';
        $this->postJson(route('welfare.applications.store'), $data)->assertUnprocessable()->assertJsonValidationErrors('applicant_contribution');
    }

    private function donationData(Fund $fund, mixed $amount): array
    {
        return ['donor_type' => 'individual', 'donor_name' => 'Example donor', 'amount' => $amount,
            'donation_date' => today()->toDateString(), 'payment_method' => 'cash',
            'designation' => 'unrestricted', 'purpose' => 'general', 'fund_id' => $fund->id];
    }
}
