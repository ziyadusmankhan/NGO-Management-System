<?php

/** Populated acceptance scenarios. This script deliberately cannot target the working database. */
use App\Domain\Business\Models\BusinessApplication;
use App\Domain\Business\Models\BusinessCategory;
use App\Domain\Business\Services\BusinessPaymentService;
use App\Domain\Business\Services\BusinessWorkflowService;
use App\Domain\Committees\Models\Committee;
use App\Domain\Documents\Services\DocumentService;
use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Finance\Funds\Services\FundBalanceService;
use App\Domain\Members\Models\Member;
use App\Domain\Operations\Models\Meeting;
use App\Domain\Operations\Models\Task;
use App\Domain\Procurement\Models\ProcurementSupplier;
use App\Domain\Procurement\Services\ProcurementOperationsService;
use App\Domain\Procurement\Services\ProcurementPaymentService;
use App\Domain\Procurement\Services\ProcurementWorkflowService;
use App\Domain\Projects\Models\ProjectCategory;
use App\Domain\Projects\Services\ProjectMonitoringService;
use App\Domain\Projects\Services\ProjectPaymentService;
use App\Domain\Projects\Services\ProjectWorkflowService;
use App\Domain\Qard\Services\QardPaymentService;
use App\Domain\Qard\Services\QardWorkflowService;
use App\Domain\Reports\Services\ReportBuilder;
use App\Domain\Users\Models\Role;
use App\Domain\Users\Models\User;
use App\Domain\Welfare\Applications\Models\WelfareApplication;
use App\Domain\Welfare\Assistance\Services\WelfareAssistancePaymentService;
use App\Domain\Welfare\Assistance\Services\WelfareAssistanceService;
use App\Domain\Welfare\Assistance\Services\WelfareMonitoringService;
use App\Domain\Welfare\Beneficiaries\Models\Beneficiary;
use App\Domain\Welfare\Verification\Models\BeneficiaryVerification;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$receipt = json_decode(file_get_contents(base_path('tmp/mysql-test-target.json')), true, flags: JSON_THROW_ON_ERROR);
$name = $receipt['database'] ?? '';
if (! app()->environment('local') || ! preg_match('/^foundation_management_test_[0-9]{14}_[a-f0-9]{4}$/', $name)
    || ($receipt['source'] ?? '') === $name || ! ($receipt['browser_seeded'] ?? false)) {
    throw new RuntimeException('Prepare a disposable browser QA database first. Working databases are forbidden.');
}
if ($receipt['realistic_seeded'] ?? false) {
    echo "Realistic QA scenarios already exist; no changes made.\n";
    exit(0);
}
config(['database.connections.qa_source_check' => config('database.connections.mysql')]);
$live = DB::connection('qa_source_check');
$before = ['users' => $live->table('users')->count(), 'ledger' => $live->table('transactions')->count()];
config(['database.connections.mysql.database' => $name, 'database.connections.mysql.url' => null, 'database.default' => 'mysql']);
DB::purge('mysql');
foreach (['private', 'public', 'local'] as $disk) {
    config(['filesystems.disks.'.$disk.'.root' => base_path('storage/framework/testing/qa-browser/'.$disk)]);
}
config(['session.driver' => 'array']);
app('session')->driver()->start();

final class RealisticQaData
{
    public array $users = [], $funds = [], $people = [], $checks = [], $links = [];
    public Committee $decision;
    public Committee $implementation;
    private array $memberIds = [];

    private function check(string $label, bool $condition): void
    {
        if (! $condition) throw new RuntimeException('QA assertion failed: '.$label);
        $this->checks[] = $label;
    }

    private function blocked(string $label, callable $operation): void
    {
        try { $operation(); } catch (Illuminate\Validation\ValidationException $e) {
            $this->checks[] = $label;
            return;
        }
        throw new RuntimeException('Expected rejection: '.$label);
    }

    private function file(string $name = 'sample-evidence.pdf'): UploadedFile
    {
        // A visibly labelled sample, never a reproduction of a real person's signature.
        $stream = "BT /F1 16 Tf 40 780 Td (SYNTHETIC QA EVIDENCE - NOT A REAL SIGNATURE) Tj ET";
        $objects = ['<< /Type /Catalog /Pages 2 0 R >>', '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>',
            '<< /Length '.strlen($stream)." >>\nstream\n".$stream."\nendstream", '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>'];
        $pdf = "%PDF-1.4\n"; $offsets = [];
        foreach ($objects as $i => $object) { $offsets[] = strlen($pdf); $pdf .= ($i + 1)." 0 obj\n".$object."\nendobj\n"; }
        $xref = strlen($pdf); $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        foreach ($offsets as $offset) $pdf .= sprintf("%010d 00000 n \n", $offset);
        $pdf .= "trailer\n<< /Root 1 0 R /Size 6 >>\nstartxref\n$xref\n%%EOF\n";
        return UploadedFile::fake()->createWithContent($name, $pdf);
    }

    /** Use the same request validators/controllers as browser submissions. Middleware is tested separately. */
    private function request(User $actor, array $data = [], string $class = Request::class): Request
    {
        Auth::setUser($actor);
        $files = [];
        foreach ($data as $key => $value) {
            if ($value instanceof UploadedFile || (is_array($value) && isset($value[0]) && $value[0] instanceof UploadedFile)) {
                $files[$key] = $value; unset($data[$key]);
            }
        }
        $request = Request::create('http://127.0.0.1:8011/qa-scenario', 'POST', $data, [], $files);
        $request->setUserResolver(fn () => $actor);
        $request->setLaravelSession(app('session')->driver());
        app()->instance('request', $request);
        if ($class !== Request::class) {
            $request = $class::createFrom($request);
            $request->setContainer(app())->setRedirector(app('redirect'));
            $request->validateResolved();
        }
        return $request;
    }

    private function version($record, array $data = []): array { return ['version' => $record->fresh()->version] + $data; }
    private function payment(string $amount, string $receipt): array
    {
        return ['amount' => $amount, 'payment_date' => now('Asia/Karachi')->toDateString(), 'payment_method' => 'cash',
            'idempotency_key' => (string) Str::uuid(), 'receipt' => $receipt, 'notes' => 'Synthetic acceptance scenario.'];
    }

    public function organization(): void
    {
        // Example configurable roles, deliberately scoped to independent duties.
        foreach (['field-verification-officer' => ['Field Verification Officer', 'welfare.beneficiaries.verify'], 'eligibility-approver' => ['Beneficiary Eligibility Approver', 'welfare.beneficiaries.approve']] as $slug => [$title, $permission]) {
            $role = Role::firstOrCreate(['slug' => $slug], ['name' => $title, 'status' => 'active']);
            $role->permissions()->sync(App\Domain\Users\Models\Permission::whereIn('slug', ['welfare.view', $permission])->pluck('id'));
        }
        $definitions = [
            'officer' => ['Amina Rahman', 'amina.rahman', ['welfare-officer', 'welfare-review-officer', 'field-verification-officer', 'qard-officer', 'small-business-officer', 'project-officer', 'procurement-officer']],
            'implementation' => ['Hamza Farooq', 'hamza.farooq', ['implementation-committee']],
            'coordinator' => ['Faisal Mahmood', 'faisal.mahmood', ['chief-coordinator']],
            'secretary' => ['Sadia Khalid', 'sadia.khalid', ['general-secretary']],
            'decision' => ['Naveed Aslam', 'naveed.aslam', ['decision-making-committee', 'eligibility-approver']],
            'treasurer' => ['Hassan Raza', 'hassan.raza', ['treasurer']],
            'receiver' => ['Bilal Ahmed', 'bilal.ahmed', ['receiving-officer']],
            'records' => ['Zara Iqbal', 'zara.iqbal', ['records-officer']],
            'auditor' => ['Maryam Shah', 'maryam.shah', ['auditor-reviewer']],
            'viewer' => ['Usman Tariq', 'usman.tariq', ['viewer']],
        ];
        $this->users['admin'] = User::where('email', 'qa.admin@example.test')->firstOrFail();
        foreach ($definitions as $key => [$name, $email, $slugs]) {
            $user = User::create(['name' => $name, 'email' => $email.'@example.test', 'password' => 'QA-test-only-2026!', 'status' => 'active']);
            $roles = Role::whereIn('slug', $slugs)->get();
            $this->check('Configured roles exist for '.$name, $roles->count() === count($slugs));
            $user->roles()->sync($roles->pluck('id'));
            $this->users[$key] = $user;
        }
        $this->request($this->users['admin']);
        $this->implementation = Committee::create(['committee_number' => 'COM-00001', 'name' => 'Community Implementation Committee', 'type' => 'implementation', 'status' => 'active', 'description' => 'Coordinates education, livelihood and neighborhood improvement work.', 'established_date' => now()->subYear()->toDateString()]);
        $this->decision = Committee::create(['committee_number' => 'COM-00002', 'name' => 'Grants and Finance Decision Committee', 'type' => 'decision', 'status' => 'active', 'description' => 'Records independent funding resolutions and reviews final accounts.', 'established_date' => now()->subYear()->toDateString()]);
        Committee::create(['committee_number' => 'COM-00003', 'name' => 'Education Outreach Committee', 'type' => 'implementation', 'status' => 'active', 'description' => 'Visits schools and follows up with families.']);
        foreach (['Shahid Hussain', 'Rubina Akhtar', 'Kamran Ali', 'Nazia Bashir', 'Iqra Saleem', 'Adnan Yousaf'] as $i => $name) {
            [$first, $last] = explode(' ', $name, 2);
            $member = Member::create(['member_number' => sprintf('MEM-%05d', $i + 1), 'first_name' => $first, 'last_name' => $last,
                'phone' => '030000001'.sprintf('%02d', $i), 'cnic' => '99999-00000'.sprintf('%02d', $i).'-1', 'email' => strtolower($first).'.member@example.test',
                'address' => 'Sample House '.($i + 10).', Kasso', 'city' => 'Kasso', 'joining_date' => now()->subMonths($i + 1)->toDateString(), 'status' => 'active']);
            ($i < 2 ? $this->decision : $this->implementation)->members()->attach($member, ['position' => $i === 0 ? 'Chairperson' : 'Member', 'status' => 'active', 'joined_at' => now()->subMonth()->toDateString()]);
            if ($i < 2) $this->memberIds[] = $member->id;
        }
        foreach (['general' => ['General Operations Fund', 'general', 'general'], 'education' => ['School Continuity Fund', 'restricted', 'education'],
            'welfare' => ['Family Welfare Fund', 'restricted', 'general_welfare'], 'qard' => ['Revolving Livelihood Qard Fund', 'restricted', 'qard'],
            'business' => ['Enterprise Development Fund', 'restricted', 'small_business'], 'projects' => ['Community Infrastructure Fund', 'restricted', 'community_development']] as $key => [$name, $type, $purpose]) {
            $this->funds[$key] = Fund::create(['fund_reference' => 'FND-'.strtoupper($key), 'name' => $name, 'type' => $type, 'purpose' => $purpose,
                'status' => 'active', 'opening_balance' => '0.00', 'prevent_negative_balance' => true]);
        }
        foreach (['Zainab Bibi', 'Imran Latif', 'Sana Parveen', 'Asif Mehmood', 'Hina Yousaf', 'Rashid Iqbal', 'Noreen Akram', 'Salman Aziz'] as $i => $name) {
            $person = Beneficiary::create(['beneficiary_number' => sprintf('BEN-%05d', $i + 1), 'full_name' => $name,
                'father_husband_name' => 'Sample Guardian '.($i + 1), 'cnic' => '99998-00000'.sprintf('%02d', $i).'-1', 'phone' => '030000002'.sprintf('%02d', $i),
                'address' => 'Sample Household '.($i + 20).', Kasso', 'location' => 'Kasso', 'category' => $i === 0 ? 'student' : 'other',
                'marital_status' => 'married', 'dependents_count' => 3 + $i % 4, 'occupation' => $i % 2 ? 'Daily wage worker' : 'Home-based worker',
                'monthly_income' => '22000.00', 'household_condition' => 'Synthetic household assessment: irregular income and school-age dependents.',
                'verification_status' => 'pending', 'approval_status' => 'pending', 'notes' => 'Fictional person for application QA only.']);
            $controller = app(App\Http\Controllers\Welfare\BeneficiaryController::class);
            $this->request($this->users['officer']); $controller->verify($person);
            $this->request($this->users['decision']); $controller->approve($person->fresh());
            $this->people[] = $person->fresh();
            $this->links[] = '/welfare/beneficiaries/'.$person->id;
        }
    }

    private function resolution(): array
    {
        return ['committee_id' => $this->decision->id, 'consenting_member_ids' => $this->memberIds, 'resolution_reference' => 'GFC-'.Str::upper(Str::random(6)),
            'resolution_date' => now('Asia/Karachi')->toDateString(), 'resolution_file' => $this->file('sample-committee-resolution.pdf')];
    }

    public function finance(): void
    {
        $controller = app(App\Http\Controllers\Finance\DonationController::class);
        foreach ([['general', 'Farooq Welfare Trust', '250000.00'], ['education', 'Ayesha Siddiqui', '90000.00'], ['welfare', 'Kasso Overseas Circle', '125000.00'],
            ['qard', 'Rehan and Family', '80000.00'], ['business', 'Sahar Community Group', '150000.00'], ['projects', 'Nisar Foundation Partners', '300000.00']] as $i => [$fund, $name, $amount]) {
            $controller->store($this->request($this->users['treasurer'], ['donor_type' => match ($fund) { 'education' => 'individual', 'qard' => 'family', default => 'organization' }, 'donor_name' => $name, 'amount' => $amount,
                'donation_date' => now()->toDateString(), 'payment_method' => 'cash', 'designation' => $fund === 'general' ? 'unrestricted' : 'restricted',
                'purpose' => match ($fund) { 'education' => 'education', 'qard' => 'qard', 'business' => 'business', default => 'general' }, 'fund_id' => $this->funds[$fund]->id, 'receipt_number' => 'DR-2026-'.($i + 1),
                'purpose_notes' => 'Synthetic donation for '.$this->funds[$fund]->name, 'internal_notes' => 'Acceptance-test funds, no real money.'], App\Http\Requests\Finance\StoreDonationRequest::class));
        }
        $expenses = app(App\Http\Controllers\Finance\ExpenseController::class);
        $balances = app(FundBalanceService::class);
        foreach ([['Office internet service — October', 'Kasso Connect Services', '4500.00'], ['Field visit transport service', 'Adeel Transport', '2800.00']] as [$description, $payee, $amount]) {
            $expenses->store($this->request($this->users['treasurer'], ['fund_id' => $this->funds['general']->id, 'category' => 'admin', 'purchase_kind' => 'service',
                'description' => $description, 'payee_name' => $payee, 'amount' => $amount, 'expense_date' => now()->toDateString(), 'payment_method' => 'cash', 'status' => 'pending'], App\Http\Requests\Finance\StoreExpenseRequest::class), $balances);
            $expense = App\Domain\Finance\Expenses\Models\Expense::latest('id')->firstOrFail();
            $this->request($this->users['admin']); $expenses->approve($expense, $balances); $expenses->markPaid($expense->fresh());
        }
        $this->check('Donations and approved expenses post through Finance services', App\Domain\Finance\Transactions\Models\Transaction::where('status', 'posted')->count() === 8);
    }

    public function welfare(): void
    {
        $c = app(App\Http\Controllers\Welfare\WelfareApplicationController::class);
        $officer = $this->users['officer'];
        foreach ([[$this->people[0], 'education', 'education_support', 'School term fees, books and examination charges.', '18500.00'],
            [$this->people[6], 'welfare', 'general_welfare', 'Temporary household support while seasonal work resumes.', '12000.00']] as $i => [$person, $fund, $type, $purpose, $amount]) {
            $c->store($this->request($officer, ['beneficiary_id' => $person->id, 'fund_id' => $this->funds[$fund]->id, 'category' => $fund === 'education' ? 'education' : 'welfare',
                'request_type' => $type, 'requested_amount' => $amount, 'purpose_details' => $purpose, 'priority' => 'normal', 'frequency' => 'one_time',
                'start_date' => now()->toDateString(), 'end_date' => now()->addMonth()->toDateString(), 'supporting_documents' => [$this->file('sample-application-support.pdf')]], App\Http\Requests\Welfare\StoreWelfareApplicationRequest::class));
            $a = WelfareApplication::latest('id')->firstOrFail();
            $this->links[] = '/welfare/applications/'.$a->id;
            if ($i === 1) continue; // A real pending queue as well as a completed case.
            $v = app(App\Http\Controllers\Welfare\BeneficiaryVerificationController::class);
            $v->store($this->request($officer, ['application_id' => $a->id, 'assigned_to' => $officer->id, 'visit_date' => now()->toDateString(), 'priority' => 'normal', 'method' => 'field_visit', 'notes' => 'Verify school enrollment and fee statement.']));
            $verification = BeneficiaryVerification::latest('id')->firstOrFail();
            $v->start($verification);
            $v->complete($this->request($officer, ['recommendation' => 'approve', 'findings' => 'Enrollment and household income checked. Fee statement matches the requested assistance.', 'evidence' => [$this->file('sample-field-visit.pdf')]]), $verification->fresh());
            $this->check('Field verification completes before welfare decisions', $a->fresh()->status === 'recommendation');
            $c->implementationDecision($this->request($this->users['implementation'], ['decision' => 'approve', 'recommended_amount' => $amount, 'notes' => 'Field evidence supports the proposed education grant.']), $a->fresh());
            $c->secretaryReview($this->request($this->users['secretary'], ['decision' => 'approve', 'notes' => 'Eligibility and the designated education fund confirmed.']), $a->fresh());
            $c->committeeDecision($this->request($this->users['decision'], ['decision' => 'approve', 'approved_amount' => $amount, 'notes' => 'Approved one-time education assistance within the fund purpose.']), $a->fresh());
            $agreement = app(App\Http\Controllers\Welfare\WelfareApplicationAgreementController::class);
            $signingOfficer = $this->users['secretary'];
            $agreement->store($this->request($signingOfficer, ['agreement_date' => now()->toDateString(), 'effective_date' => now()->toDateString(), 'terms' => 'One-time education support for the approved school fees. Synthetic acceptance scenario.']), $a->fresh());
            $agreement->prepare($a->fresh());
            foreach (['beneficiary' => $person->full_name, 'organization' => $signingOfficer->name] as $party => $name) {
                $agreement->recordSignature($this->request($signingOfficer, ['signatory_name' => $name, 'confirmed' => '1', 'document' => $this->file('sample-'.$party.'-not-a-real-signature.pdf')], App\Http\Requests\Welfare\RecordWelfareAgreementSignatureRequest::class), $a->fresh(), $party);
            }
            $service = app(WelfareAssistanceService::class);
            $assistance = $service->createFromApplication($a->id, ['internal_notes' => 'Pay against the approved fee statement.'], $officer);
            $service->transition($assistance, 'approved', $this->users['admin']);
            $payment = app(WelfareAssistancePaymentService::class);
            $data = $this->payment('7500.25', 'WA-1001') + ['receipt_number' => 'WA-1001', 'paid_to' => $person->full_name];
            $first = $payment->record($assistance->fresh(), $data, $officer);
            $again = $payment->record($assistance->fresh(), $data, $officer);
            $this->check('Welfare duplicate submission returns the original payment', $first['payment']->id === $again['payment']->id);
            $this->check('Welfare remaining authorization is PKR 10,999.75', $payment->remainingMinor($assistance) === 1099975);
            $this->blocked('Welfare rejects payment above remaining authorization', fn () => $payment->record($assistance->fresh(), $this->payment('18500.00', 'WA-OVER'), $officer));
            app(WelfareMonitoringService::class)->recordReview($assistance->fresh(), ['idempotency_key' => (string) Str::uuid(), 'expected_review_id' => 0,
                'review_date' => now()->toDateString(), 'outcome' => 'follow_up_required', 'next_review_date' => now()->toDateString(),
                'notes' => 'Confirm that the school has credited the first installment.', 'follow_up_action' => 'Contact the school office for acknowledgment.'], $officer);
            $this->links[] = '/welfare/assistance/'.$assistance->id;
        }
    }

    public function qard(): void
    {
        $w = app(QardWorkflowService::class); $officer = $this->users['officer'];
        foreach ([['500.00', $this->people[1], 'Bicycle repair tools for local repair work', true], ['36000.00', $this->people[3], 'Working capital for a home tailoring service', false]] as [$principal, $person, $purpose, $settle]) {
            $a = $w->save(null, ['beneficiary_id' => $person->id, 'fund_id' => $this->funds['qard']->id, 'purpose' => $purpose, 'principal' => $principal,
                'frequency' => 'monthly', 'installment_count' => 3, 'first_due_date' => now()->addMonthNoOverflow()->endOfMonth()->toDateString()], $officer);
            $w->submit($a, $officer); $w->verify($a, ['notes' => 'Confirmed skills, need and proposed repayment capacity.', 'evidence' => $this->file()], $officer);
            foreach (['implementation' => 'implementation', 'secretary' => 'secretary', 'committee' => 'decision'] as $stage => $key) $w->decide($a, $stage, ['decision' => 'accept', 'notes' => 'Principal and repayment schedule reviewed.'], $this->users[$key]);
            $w->sign($a, ['borrower_name' => $person->full_name, 'foundation_name' => $officer->name, 'witnesses' => ['Shahid Hussain', 'Rubina Akhtar'],
                'borrower_file' => $this->file('sample-borrower-signature.pdf'), 'foundation_file' => $this->file('sample-foundation-signature.pdf')], $officer);
            $p = app(QardPaymentService::class); $payer = $this->users['treasurer'];
            $p->record($a->fresh(), 'disbursement', $this->payment($principal, 'QD-1001'), $payer);
            $data = $this->payment($settle ? '10.25' : '12000.00', 'QR-1001');
            $first = $p->record($a->fresh(), 'repayment', $data, $payer); $again = $p->record($a->fresh(), 'repayment', $data, $payer);
            $this->check('Qard repayment retry posts once for '.$person->full_name, $first->id === $again->id);
            if ($settle) {
                $this->blocked('Qard rejects PKR 500 after recovering PKR 10.25', fn () => $p->record($a->fresh(), 'repayment', $this->payment('500.00', 'QR-OVER'), $payer));
                $p->record($a->fresh(), 'repayment', $this->payment('489.75', 'QR-1002'), $payer);
                $this->check('Qard settles exactly with PKR 489.75', $a->fresh()->status === 'repaid');
            } else $w->followup($a->fresh(), ['notes' => 'Borrower confirmed the remaining installment dates. Review the order book at the next visit.', 'next_followup_date' => now()->toDateString()], $officer);
            $this->links[] = '/qard/'.$a->id;
        }
    }

    public function business(): void
    {
        $w = app(BusinessWorkflowService::class); $officer = $this->users['officer'];
        $category = BusinessCategory::create(['name' => 'Home enterprise', 'active' => true]);
        $a = $w->save(null, ['beneficiary_id' => $this->people[2]->id, 'fund_id' => $this->funds['business']->id, 'category_id' => $category->id,
            'name' => 'Sana Home Stitching', 'location' => 'Kasso', 'description' => 'Expand an established home-based clothing alteration service.', 'skills' => 'Three years of tailoring and customer referrals.',
            'market_assessment' => 'School uniform repairs and local household demand.', 'risks' => 'Seasonal demand and fabric price changes.', 'expected_start_date' => now()->addWeek()->toDateString(),
            'expected_sales' => '45000.00', 'expected_expenses' => '24000.00', 'support_type' => 'repayable', 'frequency' => 'monthly', 'installment_count' => 6,
            'items' => [['description' => 'Working cash for customer fabric orders', 'cost' => '45000.00', 'applicant_amount' => '5000.00', 'release_method' => 'cash', 'price_checks' => []]]], $officer);
        $w->submit($a, $this->version($a), $officer); $w->verify($a, $this->version($a, ['notes' => 'Visited workspace and reviewed customer order book.', 'evidence' => $this->file()]), $officer);
        foreach (['implementation' => 'coordinator', 'secretary' => 'secretary', 'committee' => 'decision'] as $stage => $key) {
            $data = ['decision' => 'accept', 'notes' => 'Business viability and the repayment plan are acceptable.'];
            if ($stage === 'committee') $data += $this->resolution();
            $w->decide($a, $stage, $this->version($a, $data), $this->users[$key]);
        }
        $a = $a->fresh();
        $signature = fn () => ['borrower_name' => $this->people[2]->full_name, 'foundation_name' => $officer->name, 'witnesses' => ['Shahid Hussain', 'Rubina Akhtar'],
            'signed_on' => now()->toDateString(), 'borrower_file' => $this->file('sample-business-borrower.pdf'), 'foundation_file' => $this->file('sample-business-foundation.pdf')];
        $w->sign($a, $this->version($a, $signature() + ['contribution_confirmed' => '1', 'terms_hash' => $w->hash($w->terms($a))]), $officer);
        $p = app(BusinessPaymentService::class); $payer = $this->users['treasurer'];
        $p->record($a->fresh(), 'disbursement', $this->version($a, $this->payment('40000.00', 'BD-1001') + ['budget_item_id' => $a->currentBudget()->first()->id, 'payee' => $this->people[2]->full_name, 'evidence' => $this->file()]), $payer);
        $p->finalize($a->fresh(), $this->version($a, $signature() + ['reason' => 'All approved funding released; final principal acknowledged.', 'terms_hash' => $w->hash($p->finalTerms($a->fresh()))]), $payer);
        $p->record($a->fresh(), 'repayment', $this->version($a, $this->payment('5000.00', 'BR-1001') + ['payee' => $this->people[2]->full_name]), $payer);
        $monitor = app(App\Domain\Business\Services\BusinessMonitoringService::class);
        $monitor->record($a, 'start', $this->version($a, ['started_on' => now()->toDateString(), 'evidence' => $this->file()]), $officer);
        $monitor->record($a, 'monitor', $this->version($a, ['review_date' => now()->toDateString(), 'condition' => 'running', 'findings' => 'Customer orders are being completed; repayment record checked.',
            'actions' => 'Keep a weekly sales and expense notebook.', 'sales' => '18000.00', 'expenses' => '9500.00', 'final_report' => false, 'next_review_date' => now()->addMonth()->toDateString()]), $officer);
        $this->check('Business case retains one disbursement and one recovery', $a->movements()->where('status', 'posted')->count() === 2);
        $this->links[] = '/business/applications/'.$a->id;
    }

    public function projects(): void
    {
        $w = app(ProjectWorkflowService::class); $officer = $this->users['officer'];
        $category = ProjectCategory::create(['name' => 'Community access', 'purpose' => 'community_development', 'active' => true]);
        $p = $w->save(null, ['name' => 'Kasso School Access Path Repair', 'description' => 'Repair the damaged footpath leading to the community school.', 'location' => 'School Lane, Kasso',
            'outcomes' => 'Safer access for children and nearby households.', 'approach' => 'Local workers repair and level the existing pathway.', 'beneficiaries' => 120,
            'category_id' => $category->id, 'fund_id' => $this->funds['projects']->id, 'committee_id' => $this->implementation->id, 'responsible_id' => $officer->id,
            'planned_start' => now()->toDateString(), 'planned_completion' => now()->addMonth()->toDateString(),
            'items' => [['description' => 'Path leveling and masonry labor', 'kind' => 'labor', 'payee' => 'Rafiq Masonry Services', 'amount' => '65000.00', 'estimate' => 'Labor estimate RMS-1026, agreed scope and inclusive price.', 'price_checks' => []]], 'evidence' => $this->file('sample-project-proposal.pdf')], $officer);
        $w->submit($p, $this->version($p), $officer); $w->verify($p, $this->version($p, ['notes' => 'Measured damaged section and confirmed the school access route.', 'evidence' => $this->file()]), $officer);
        foreach (['coordinator' => 'coordinator', 'secretary' => 'secretary', 'committee' => 'decision'] as $stage => $key) {
            $data = ['decision' => 'accept', 'notes' => 'Scope, cost estimate and responsible team reviewed.'];
            if ($stage === 'committee') { $data += $this->resolution(); $data['evidence'] = $data['resolution_file']; unset($data['resolution_file']); }
            $w->decide($p, $stage, $this->version($p, $data), $this->users[$key]);
        }
        $monitor = app(ProjectMonitoringService::class);
        $monitor->record($p, 'start', $this->version($p, ['date' => now()->toDateString(), 'evidence' => $this->file()]), $officer);
        $monitor->record($p, 'monitor', $this->version($p, ['date' => now()->toDateString(), 'percent' => 35, 'findings' => 'Damaged sections cleared and initial leveling completed.', 'risks' => 'Rain may delay finishing work.', 'next_review_date' => now()->addWeek()->toDateString()]), $officer);
        $payments = app(ProjectPaymentService::class);
        $data = $this->version($p, $this->payment('20000.25', 'PP-1001') + ['budget_item_id' => $p->budgets($p->approved_revision)->first()->budget_item_id, 'payee' => 'Rafiq Masonry Services', 'evidence' => $this->file()]);
        $move = $payments->record($p->fresh(), 'payment', $data, $this->users['treasurer']);
        $refund = $this->version($p, $this->payment('1000.00', 'PR-1001') + ['original_payment_id' => $move->id, 'payee' => 'Rafiq Masonry Services', 'evidence' => $this->file()]);
        $payments->record($p->fresh(), 'refund', $refund, $this->users['treasurer']);
        $this->check('Project monitoring reaches In Progress with 35% completion', $p->fresh()->status === 'in_progress' && $p->fresh()->progress_percent === 35);
        $this->links[] = '/projects/'.$p->id;
    }

    public function procurement(): void
    {
        $officer = $this->users['officer']; $w = app(ProcurementWorkflowService::class);
        $suppliers = [];
        foreach (['Kasso Office Furnishings', 'Valley Furniture House'] as $name) $suppliers[] = ProcurementSupplier::create(['name' => $name, 'name_key' => mb_strtolower($name), 'active' => true, 'contact_name' => 'Sales desk', 'email' => Str::slug($name).'@example.test']);
        $p = $w->save(null, ['title' => 'Community office visitor seating', 'description' => 'Replace unsafe visitor chairs in the interview area.', 'parent_type' => 'office',
            'fund_id' => $this->funds['general']->id, 'supplier_id' => $suppliers[0]->id, 'delivery_location' => 'Foundation office, Kasso', 'required_on' => now()->addDays(3)->toDateString(),
            'items' => [['description' => 'Visitor chair, steel frame', 'unit' => 'each', 'quantity' => '4', 'unit_price' => '5500.00']]], $officer);
        foreach ($suppliers as $i => $supplier) $w->quote($p, $this->version($p, ['supplier_id' => $supplier->id, 'reference' => 'QTN-2026-'.($i + 1), 'quoted_on' => now()->toDateString(),
            'valid_until' => now()->addMonth()->toDateString(), 'terms' => 'Delivery included; no advance; all taxes included.', 'items' => [['key' => $p->proposal()->items[0]['key'], 'unit_price' => $i ? '5900.00' : '5500.00']], 'evidence' => $this->file('sample-supplier-quotation.pdf')]), $officer);
        $w->transition($p, 'submit', $this->version($p), $officer);
        foreach (['coordinator' => 'coordinator', 'secretary' => 'secretary', 'committee' => 'decision'] as $stage => $key) {
            $data = ['decision' => 'accept', 'notes' => 'Equivalent chair specifications compared; lowest valid quotation selected.'];
            if ($stage === 'committee') { $data += $this->resolution(); $data['evidence'] = $data['resolution_file']; unset($data['resolution_file']); }
            $w->decide($p, $stage, $this->version($p, $data), $this->users[$key]);
        }
        $w->transition($p, 'issue', $this->version($p), $officer);
        $p = $p->fresh();
        $ops = app(ProcurementOperationsService::class);
        $ops->receive($p, $this->version($p, ['item_key' => $p->approved()->items[0]['key'], 'received_on' => now()->toDateString(), 'reference' => 'DN-1042',
            'accepted' => '4', 'rejected' => '0', 'notes' => 'Checked frame stability and quantity; all chairs accepted.', 'evidence' => $this->file('sample-delivery-acceptance.pdf')]), $this->users['receiver']);
        $ops->invoice($p, $this->version($p, ['reference' => 'KOF-2026-1042', 'invoiced_on' => now()->toDateString(), 'due_on' => now()->addWeek()->toDateString(),
            'lines' => [['receipt_id' => $p->receipts()->first()->id, 'quantity' => '4']], 'evidence' => $this->file('sample-supplier-invoice.pdf')]), $officer);
        $data = $this->version($p, $this->payment('10000.25', 'OP-1001') + ['invoice_line_id' => $p->lines()->first()->id, 'evidence' => $this->file('sample-payment-receipt.pdf')]);
        $payments = app(ProcurementPaymentService::class);
        $move = $payments->record($p, 'pay', $data, $this->users['treasurer']);
        $again = $payments->record($p, 'pay', $data, $this->users['treasurer']);
        $this->check('Procurement payment retry has one authoritative movement', $move->id === $again->id);
        $this->links[] = '/operations/procurement/'.$p->id;
    }

    public function operationsAndRecords(): void
    {
        $manager = $this->users['admin']; $tasks = app(App\Http\Controllers\Operations\TaskController::class);
        foreach ([['Collect school fee acknowledgment', 'welfare', 'high', 'officer'], ['Reconcile October cash receipts', 'finance', 'high', 'treasurer'], ['Photograph school access path progress', 'project', 'medium', 'officer'], ['Prepare community outreach schedule', 'administration', 'medium', 'secretary']] as $i => [$title, $category, $priority, $who]) {
            $tasks->store($this->request($manager, ['title' => $title, 'category' => $category, 'priority' => $priority, 'assigned_user_id' => $this->users[$who]->id,
                'starts_on' => now()->toDateString(), 'due_on' => now()->addDays($i + 1)->toDateString(), 'description' => 'Record completion evidence and update the responsible committee.', 'expected_outcome' => 'Verified completion with a retained update.']));
            $task = Task::latest('id')->firstOrFail();
            $tasks->status($this->request($manager, ['revision' => $task->revision, 'status' => $i === 0 ? 'completed' : ($i === 1 ? 'in_progress' : 'pending'), 'progress_percent' => $i === 0 ? 100 : ($i === 1 ? 40 : 0), 'completion_notes' => $i === 0 ? 'School acknowledged receipt.' : null]), $task);
            $tasks->comment($this->request($this->users[$who], ['body' => $i === 0 ? 'Acknowledgment received and checked.' : 'Assigned work accepted; next update will follow the scheduled visit.']), $task);
            $this->links[] = '/operations/tasks/'.$task->id;
        }
        $meetings = app(App\Http\Controllers\Operations\MeetingController::class);
        foreach ([['October community coordination review', now()->toDateString()], ['November field review planning', now()->addWeek()->toDateString()]] as $i => [$title, $date]) {
            $meetings->store($this->request($manager, ['title' => $title, 'type' => 'general', 'meeting_date' => $date, 'starts_at' => '10:00', 'ends_at' => '11:00', 'location' => 'Foundation office, Kasso',
                'agenda' => 'Review school support, livelihood repayments and community project progress.', 'chair_user_id' => $this->users['secretary']->id, 'quorum_required' => 2,
                'attendees' => [$manager->id, $this->users['secretary']->id, $this->users['officer']->id]]));
            $meeting = Meeting::latest('id')->firstOrFail();
            if ($i === 0) $meetings->minutes($this->request($manager, ['revision' => $meeting->revision, 'minutes' => 'Reviewed current cases and checked the progress of the school access path.',
                'decisions' => 'Continue weekly follow-ups and collect the next progress photographs.', 'attended' => [$manager->id, $this->users['secretary']->id, $this->users['officer']->id],
                'action_items' => [['title' => 'Share project progress at next coordination review', 'assigned_user_id' => $this->users['officer']->id, 'due_on' => now()->addWeek()->toDateString(), 'priority' => 'medium']]]), $meeting);
            $this->links[] = '/operations/meetings/'.$meeting->id;
        }
        $documents = app(DocumentService::class);
        foreach (['Safeguarding and confidentiality policy', 'Volunteer field visit checklist', 'October coordination minutes'] as $i => $title) {
            $d = $documents->save($this->users['records'], ['title' => $title, 'category' => $i === 2 ? 'Minutes' : 'Policy', 'document' => $this->file(Str::slug($title).'.pdf')]);
            $documents->change($this->users['records'], $d, 'share', ['revision' => $d->revision, 'roles' => Role::whereIn('slug', ['auditor-reviewer', 'general-secretary'])->pluck('id')->all()]);
            $this->links[] = '/records/documents/'.$d->id;
        }
        $reports = app(App\Http\Controllers\Records\ReportController::class); $builder = app(ReportBuilder::class);
        foreach (array_keys(ReportBuilder::TYPES) as $type) $reports->store($this->request($manager, ['type' => $type, 'from' => now()->startOfMonth()->toDateString(), 'to' => now()->endOfMonth()->toDateString()]), $builder);
        $this->check('All eight Records report types generate populated retained snapshots', App\Domain\Reports\Models\ReportRun::count() === 8);
        $this->check('Meeting minutes create an assigned follow-up task', Task::whereNotNull('source_meeting_id')->exists());
    }

    public function verify(): void
    {
        $tx = App\Domain\Finance\Transactions\Models\Transaction::class;
        $this->check('Every source movement has exactly one ledger record', ! DB::table('transactions')->select('source_type', 'source_id')->groupBy('source_type', 'source_id')->havingRaw('COUNT(*) > 1')->exists());
        foreach (['App\\Domain\\Qard\\Models\\QardMovement', 'App\\Domain\\Business\\Models\\BusinessMovement', 'App\\Domain\\Projects\\Models\\ProjectMovement', 'App\\Domain\\Procurement\\Models\\ProcurementMovement'] as $class) {
            foreach ($class::where('status', 'posted')->get() as $movement) {
                $ledger = $tx::where('source_type', $movement->getMorphClass())->where('source_id', $movement->id)->first();
                $this->check(class_basename($class).' '.$movement->id.' matches its original fund and amount', $ledger && (int) $ledger->fund_id === (int) $movement->fund_id && $ledger->amount === $movement->amount);
            }
        }
        foreach ($this->funds as $fund) $this->check($fund->name.' remains solvent', app(FundBalanceService::class)->balanceMinor($fund) >= 0);
        $this->check('Sensitive beneficiary phone is encrypted in MySQL', str_starts_with(DB::table('beneficiaries')->where('id', $this->people[0]->id)->value('phone'), 'encrypted:v1:'));
    }
}

$qa = new RealisticQaData;
try {
    DB::transaction(function () use ($qa) {
        foreach (['organization', 'finance', 'welfare', 'qard', 'business', 'projects', 'procurement', 'operationsAndRecords', 'verify'] as $section) {
            echo 'Building and verifying '.$section."...\n";
            $qa->$section();
        }
    });
} catch (Throwable $e) {
    // Show field validation only; never print SQL, connection credentials or request bodies.
    $detail = $e instanceof Illuminate\Validation\ValidationException ? json_encode($e->errors())
        : ($e instanceof Illuminate\Database\QueryException ? 'Database error code '.$e->getCode().' at '.$e->getFile().':'.$e->getLine() : $e->getMessage());
    fwrite(STDERR, get_class($e).': '.$detail."\n");
    exit(1);
}
$receipt['realistic_seeded'] = true;
file_put_contents(base_path('tmp/mysql-test-target.json'), json_encode($receipt, JSON_PRETTY_PRINT));
$counts = [];
foreach (['users', 'members', 'committees', 'funds', 'donations', 'expenses', 'transactions', 'beneficiaries', 'welfare_applications', 'welfare_assistances', 'qard_loans', 'business_applications', 'projects', 'procurements', 'operation_tasks', 'operation_meetings', 'library_documents', 'report_runs', 'audit_events'] as $table) $counts[$table] = DB::table($table)->count();
$after = ['users' => $live->table('users')->count(), 'ledger' => $live->table('transactions')->count()];
$report = ['database' => $name, 'synthetic_only' => true, 'working_database_counts_unchanged' => $before === $after, 'counts' => $counts, 'checks' => $qa->checks, 'detail_paths' => $qa->links];
file_put_contents(base_path('tmp/qa-realistic-data.json'), json_encode($report, JSON_PRETTY_PRINT));
echo json_encode($report, JSON_PRETTY_PRINT), PHP_EOL;
