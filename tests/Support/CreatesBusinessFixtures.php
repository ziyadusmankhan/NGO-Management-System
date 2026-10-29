<?php

namespace Tests\Support;

use App\Domain\Business\Models\BusinessApplication;
use App\Domain\Business\Models\BusinessCategory;
use App\Domain\Business\Services\BusinessPaymentService;
use App\Domain\Business\Services\BusinessWorkflowService;
use App\Domain\Committees\Models\Committee;
use App\Domain\Members\Models\Member;
use App\Domain\Users\Models\User;
use Database\Seeders\BusinessRoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

trait CreatesBusinessFixtures
{
    use CreatesWelfareFixtures;

    protected function businessActor(): User
    {
        return $this->staff(array_merge(array_map(fn ($a) => 'business.'.$a, BusinessRoleSeeder::PERMISSIONS), ['finance.view', 'qard.view', 'finance.reports.view']));
    }

    protected function evidence(string $name = 'evidence.pdf'): UploadedFile
    {
        return ValidUpload::pdf($name, 20);
    }

    protected function businessData(User $actor, string $type = 'repayable'): array
    {
        $existing = $this->signedApplication($actor);
        $existing->fund->update(['type' => 'restricted', 'purpose' => 'small_business', 'opening_balance' => '1000.00']);
        $category = BusinessCategory::firstOrCreate(['name' => 'Retail'], ['active' => true]);

        return ['beneficiary_id' => $existing->beneficiary_id, 'fund_id' => $existing->fund_id, 'category_id' => $category->id,
            'name' => 'Test business', 'location' => 'Kasso', 'description' => 'Start a small shop', 'skills' => 'Retail experience',
            'market_assessment' => 'Customers nearby', 'risks' => 'Stock control', 'expected_start_date' => now()->addWeek()->toDateString(),
            'expected_sales' => '1000.00', 'expected_expenses' => '300.00', 'support_type' => $type, 'frequency' => 'monthly', 'installment_count' => 3,
            'items' => [['description' => 'Initial stock', 'cost' => '600.00', 'applicant_amount' => '100.00', 'release_method' => 'cash', 'price_checks' => []]],
        ];
    }

    protected function draftBusiness(User $actor, string $type = 'repayable'): BusinessApplication
    {
        return app(BusinessWorkflowService::class)->save(null, $this->businessData($actor, $type), $actor);
    }

    protected function versioned(BusinessApplication $a, array $data = []): array
    {
        return ['version' => $a->fresh()->version] + $data;
    }

    protected function committeeResolution(): array
    {
        $committee = Committee::create(['committee_number' => 'COM-'.Str::random(8), 'name' => 'Decision committee', 'type' => 'decision', 'status' => 'active']);
        $ids = [];
        for ($i = 0; $i < 2; $i++) {
            $member = Member::create(['member_number' => 'MEM-'.Str::random(8), 'first_name' => 'Committee member '.$i, 'status' => 'active', 'joining_date' => now()->toDateString()]);
            $committee->members()->attach($member->id, ['position' => 'Member', 'status' => 'active', 'joined_at' => now()->toDateString()]);
            $ids[] = $member->id;
        }

        return ['committee_id' => $committee->id, 'consenting_member_ids' => $ids, 'resolution_reference' => 'RES-01', 'resolution_date' => now(config('business.timezone'))->toDateString(), 'resolution_file' => $this->evidence('resolution.pdf')];
    }

    protected function approveBusiness(BusinessApplication $a, User $actor): BusinessApplication
    {
        $w = app(BusinessWorkflowService::class);
        $w->submit($a, $this->versioned($a), $actor);
        $w->verify($a, $this->versioned($a, ['notes' => 'Viable and verified', 'evidence' => $this->evidence()]), $actor);
        foreach (['implementation', 'secretary', 'committee'] as $stage) {
            $data = ['decision' => 'accept', 'notes' => 'Approved'];
            if ($stage === 'committee') {
                $data += $this->committeeResolution();
            }
            $w->decide($a, $stage, $this->versioned($a, $data), $this->businessActor());
        }

        return $a->fresh();
    }

    protected function businessSignatures(): array
    {
        return ['borrower_name' => 'Borrower', 'foundation_name' => 'Foundation', 'witnesses' => ['Witness one', 'Witness two'], 'signed_on' => now(config('business.timezone'))->toDateString(), 'borrower_file' => $this->evidence('borrower.pdf'), 'foundation_file' => $this->evidence('foundation.pdf')];
    }

    protected function signedBusiness(User $actor, string $type = 'repayable'): BusinessApplication
    {
        $a = $this->approveBusiness($this->draftBusiness($actor, $type), $actor);
        $w = app(BusinessWorkflowService::class);
        $w->sign($a, $this->versioned($a, $this->businessSignatures() + ['contribution_confirmed' => '1', 'terms_hash' => $w->hash($w->terms($a))]), $actor);

        return $a->fresh();
    }

    protected function businessPayment(BusinessApplication $a, string $kind, string $amount = '500.00'): array
    {
        return $this->versioned($a, ['amount' => $amount, 'idempotency_key' => (string) Str::uuid(), 'payment_date' => now(config('business.timezone'))->toDateString(), 'payment_method' => 'cash', 'payee' => 'Borrower', 'receipt' => 'REC-1'] +
            ($kind === 'disbursement' ? ['budget_item_id' => $a->currentBudget()->first()->id, 'evidence' => $this->evidence()] : []));
    }

    protected function finalization(BusinessApplication $a): array
    {
        $a = $a->fresh();

        return $this->versioned($a, ['terms_hash' => app(BusinessWorkflowService::class)->hash(app(BusinessPaymentService::class)->finalTerms($a)), 'reason' => 'Funding complete'] + ($a->support_type === 'repayable' ? $this->businessSignatures() : []));
    }

    protected function fundedBusiness(User $actor, string $type = 'repayable'): BusinessApplication
    {
        $a = $this->signedBusiness($actor, $type);
        $payer = $this->businessActor();
        app(BusinessPaymentService::class)->record($a, 'disbursement', $this->businessPayment($a, 'disbursement'), $payer);
        app(BusinessPaymentService::class)->finalize($a->fresh(), $this->finalization($a), $payer);

        return $a->fresh();
    }
}
