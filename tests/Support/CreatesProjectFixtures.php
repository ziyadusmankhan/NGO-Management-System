<?php

namespace Tests\Support;

use App\Domain\Committees\Models\Committee;
use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectCategory;
use App\Domain\Projects\Services\ProjectWorkflowService;
use App\Domain\Users\Models\User;
use Database\Seeders\ProjectRoleSeeder;
use Illuminate\Support\Str;

trait CreatesProjectFixtures
{
    use CreatesBusinessFixtures;

    protected function projectActor(): User
    {
        return $this->staff([...array_map(fn ($a) => 'projects.'.$a, ProjectRoleSeeder::PERMISSIONS), 'finance.view', 'finance.funds.manage', 'finance.donations.manage', 'finance.expenses.manage', 'finance.expenses.approve', 'finance.reports.view']);
    }

    protected function projectData(User $actor): array
    {
        $fund = Fund::create(['fund_reference' => 'FND-'.Str::ulid(), 'name' => 'Community fund', 'type' => 'restricted', 'purpose' => 'community_development', 'status' => 'active', 'opening_balance' => '1000.00']);
        $committee = Committee::create(['committee_number' => 'COM-'.Str::ulid(), 'name' => 'Implementation', 'type' => 'implementation', 'status' => 'active']);
        $category = ProjectCategory::firstOrCreate(['name' => 'Road repairs'], ['purpose' => 'community_development', 'active' => true]);

        return ['name' => 'Repair village road', 'description' => 'Repair a damaged road', 'location' => 'Kasso', 'outcomes' => 'Safe access', 'approach' => 'Local skilled workers', 'beneficiaries' => 100, 'category_id' => $category->id, 'fund_id' => $fund->id, 'committee_id' => $committee->id, 'responsible_id' => $actor->id,
            'planned_start' => now()->toDateString(), 'planned_completion' => now()->addMonth()->toDateString(),
            'items' => [['description' => 'Repair labor', 'kind' => 'labor', 'payee' => 'Worker One', 'amount' => '500.00', 'estimate' => 'Labor quote Q-1', 'price_checks' => []]], 'evidence' => $this->evidence()];
    }

    protected function projectVersion(Project $p, array $d = []): array
    {
        return ['version' => $p->fresh()->version] + $d;
    }

    protected function draftProject(User $actor): Project
    {
        return app(ProjectWorkflowService::class)->save(null, $this->projectData($actor), $actor);
    }

    protected function approveProject(Project $p, User $officer): Project
    {
        $w = app(ProjectWorkflowService::class);
        $w->submit($p, $this->projectVersion($p), $officer);
        $w->verify($p, $this->projectVersion($p, ['notes' => 'Verified', 'evidence' => $this->evidence()]), $officer);
        foreach (['coordinator', 'secretary', 'committee'] as $stage) {
            $d = ['decision' => 'accept', 'notes' => 'Agreed'];
            if ($stage === 'committee') {
                $d += $this->committeeResolution();
                $d['evidence'] = $d['resolution_file'];
                unset($d['resolution_file']);
            }
            $w->decide($p, $stage, $this->projectVersion($p, $d), $this->projectActor());
        }

return $p->fresh();
    }

    protected function projectPayment(Project $p, string $amount = '500.00'): array
    {
        return $this->projectVersion($p, ['amount' => $amount, 'budget_item_id' => $p->budgets($p->approved_revision)->first()->budget_item_id, 'payee' => 'Worker One', 'payment_date' => now(config('projects.timezone'))->toDateString(), 'payment_method' => 'cash', 'receipt' => 'PAY-1', 'idempotency_key' => (string) Str::uuid(), 'evidence' => $this->evidence()]);
    }

    protected function refundData(Project $p, $m, string $amount = '100.00'): array
    {
        $d = $this->projectPayment($p, $amount);
        unset($d['budget_item_id']);
        $d['original_payment_id'] = $m->id;

        return $d;
    }

    protected function projectRevisionData(Project $p, string $amount): array
    {
        $r = $p->fresh()->proposal();

        return $this->projectVersion($p, $r->only(['name', 'description', 'location', 'outcomes', 'approach', 'beneficiaries', 'category_id', 'committee_id', 'responsible_id']) + ['planned_start' => $r->planned_start->toDateString(), 'planned_completion' => $r->planned_completion->toDateString(), 'fund_id' => $p->fund_id, 'items' => [['budget_item_id' => $p->budgets()->first()->budget_item_id, 'description' => 'Repair labor', 'kind' => 'labor', 'payee' => 'Worker One', 'amount' => $amount, 'estimate' => 'Revised quote', 'price_checks' => []]], 'evidence' => $this->evidence()]);
    }
}
