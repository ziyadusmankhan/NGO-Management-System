<?php

namespace App\Domain\Reports\Services;

use App\Domain\Business\Models\BusinessApplication;
use App\Domain\Business\Models\BusinessMovement;
use App\Domain\Committees\Models\Committee;
use App\Domain\Finance\Support\Money;
use App\Domain\Finance\Transactions\Models\Transaction;
use App\Domain\Members\Models\Member;
use App\Domain\Operations\Models\Meeting;
use App\Domain\Operations\Models\Task;
use App\Domain\Procurement\Models\Procurement;
use App\Domain\Procurement\Models\ProcurementMovement;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectMovement;
use App\Domain\Qard\Models\QardLoan;
use App\Domain\Qard\Models\QardMovement;
use App\Domain\Users\Models\User;
use App\Domain\Welfare\Assistance\Models\WelfareAssistance;
use App\Domain\Welfare\Assistance\Models\WelfareAssistancePayment;
use Illuminate\Database\Eloquent\Builder;

class ReportBuilder
{
    public const TYPES = [
        'finance' => ['title' => 'Financial Summary', 'permissions' => ['finance.reports.view'], 'description' => 'Posted credits, debits and ledger movement.'],
        'welfare' => ['title' => 'Welfare Assistance', 'permissions' => ['welfare.assistance.view'], 'description' => 'Assistance cases and posted payments.'],
        'qard' => ['title' => 'Qard-e-Hasana', 'permissions' => ['qard.view'], 'description' => 'Loans, disbursements, recoveries and outstanding principal.'],
        'business' => ['title' => 'Small Business', 'permissions' => ['business.view'], 'description' => 'Business cases, funding and recoveries.'],
        'projects' => ['title' => 'Community Projects', 'permissions' => ['projects.view'], 'description' => 'Project lifecycle and posted spending.'],
        'procurement' => ['title' => 'Procurement', 'permissions' => ['procurement.view'], 'description' => 'Purchasing workflow, payments and refunds.'],
        'operations' => ['title' => 'Operations Activity', 'permissions' => ['tasks.view', 'meetings.view'], 'description' => 'Tasks, deadlines, meetings and action items.'],
        'organization' => ['title' => 'Organization Overview', 'permissions' => ['members.view', 'committees.view', 'users.view'], 'description' => 'Members, users and committees.'],
    ];

    public function allowed(User $user): array
    {
        return array_filter(self::TYPES, fn ($definition) => collect($definition['permissions'])->every(fn ($permission) => $user->hasPermission($permission)));
    }

    private function dates(Builder $query, string $column, array $filters): Builder
    {
        return $query->when($filters['from'] ?? null, fn ($q, $date) => $q->whereDate($column, '>=', $date))
            ->when($filters['to'] ?? null, fn ($q, $date) => $q->whereDate($column, '<=', $date));
    }

    private function statuses(Builder $query, string $column = 'status'): array
    {
        return $query->select([])->selectRaw($column.', COUNT(*) aggregate')->groupBy($column)->orderBy($column)->get()
            ->map(fn ($row) => ['label' => str((string) $row->{$column})->replace('_', ' ')->headline()->toString(), 'count' => (int) $row->aggregate])->all();
    }

    private function metric(string $label, int|string $value, string $format = 'number'): array
    {
        return compact('label', 'value', 'format');
    }

    public function build(string $type, array $filters): array
    {
        abort_unless(isset(self::TYPES[$type]), 404);
        $definition = self::TYPES[$type];
        $snapshot = match ($type) {
            'finance' => $this->finance($filters), 'welfare' => $this->welfare($filters), 'qard' => $this->qard($filters),
            'business' => $this->business($filters), 'projects' => $this->projects($filters), 'procurement' => $this->procurement($filters),
            'operations' => $this->operations($filters), 'organization' => $this->organization($filters),
        };

        return ['title' => $definition['title'], 'description' => $definition['description'], 'generated_at' => now('Asia/Karachi')->toIso8601String(), 'metrics' => $snapshot['metrics'], 'rows' => $snapshot['rows'], 'note' => $snapshot['note'] ?? 'Figures are generated from the current application records for the selected period.'];
    }

    private function finance(array $f): array
    {
        $base = $this->dates(Transaction::where('status', 'posted'), 'transaction_date', $f);
        $credits = Money::sum((clone $base)->where('direction', 'credit'));
        $debits = Money::sum((clone $base)->where('direction', 'debit'));
        $rows = (clone $base)->select([])->selectRaw('type, direction, COUNT(*) aggregate')->groupBy('type', 'direction')->orderBy('type')->get()->map(fn ($r) => ['label' => str($r->type)->headline()->toString().' · '.str($r->direction)->headline(), 'count' => (int) $r->aggregate])->all();

        return ['metrics' => [$this->metric('Posted credits', $credits, 'money'), $this->metric('Posted debits', $debits, 'money'), $this->metric('Net movement', Money::decimal(Money::minor($credits) - Money::minor($debits)), 'money'), $this->metric('Transactions', (clone $base)->count())], 'rows' => $rows, 'note' => 'Only posted ledger transactions are included. Net movement is credits minus debits during the selected period.'];
    }

    private function welfare(array $f): array
    {
        $cases = $this->dates(WelfareAssistance::query(), 'created_at', $f);
        $payments = $this->dates(WelfareAssistancePayment::where('status', 'posted'), 'payment_date', $f);

        return ['metrics' => [$this->metric('Cases created', (clone $cases)->count()), $this->metric('Approved cases', (clone $cases)->where('status', 'approved')->count()), $this->metric('Posted payments', (clone $payments)->count()), $this->metric('Amount paid', Money::sum($payments), 'money')], 'rows' => $this->statuses(clone $cases)];
    }

    private function qard(array $f): array
    {
        $loans = $this->dates(QardLoan::query(), 'created_at', $f);
        $moves = $this->dates(QardMovement::where('status', 'posted'), 'payment_date', $f);
        $disbursed = Money::sum((clone $moves)->where('kind', 'disbursement'));
        $repaid = Money::sum((clone $moves)->where('kind', 'repayment'));
        $allDisbursed = Money::sum(QardMovement::where('status', 'posted')->where('kind', 'disbursement'));
        $allRepaid = Money::sum(QardMovement::where('status', 'posted')->where('kind', 'repayment'));

        return ['metrics' => [$this->metric('Loans created', (clone $loans)->count()), $this->metric('Disbursed in period', $disbursed, 'money'), $this->metric('Recovered in period', $repaid, 'money'), $this->metric('Current outstanding', Money::decimal(Money::minor($allDisbursed) - Money::minor($allRepaid)), 'money')], 'rows' => $this->statuses(clone $loans), 'note' => 'Period amounts use posted Qard movements. Current outstanding is the lifetime posted balance as of generation.'];
    }

    private function business(array $f): array
    {
        $cases = $this->dates(BusinessApplication::withTrashed(), 'created_at', $f);
        $moves = $this->dates(BusinessMovement::where('status', 'posted'), 'payment_date', $f);

        return ['metrics' => [$this->metric('Cases created', (clone $cases)->count()), $this->metric('Businesses started', (clone $cases)->where('operating_status', 'started')->count()), $this->metric('Funding released', Money::sum((clone $moves)->where('kind', 'disbursement')), 'money'), $this->metric('Principal recovered', Money::sum((clone $moves)->where('kind', 'repayment')), 'money')], 'rows' => $this->statuses(clone $cases)];
    }

    private function projects(array $f): array
    {
        $projects = $this->dates(Project::withTrashed(), 'created_at', $f);
        $moves = $this->dates(ProjectMovement::where('status', 'posted'), 'payment_date', $f);

        return ['metrics' => [$this->metric('Projects created', (clone $projects)->count()), $this->metric('In progress', Project::where('status', 'in_progress')->count()), $this->metric('Gross payments', Money::sum((clone $moves)->where('kind', 'payment')), 'money'), $this->metric('Refunds', Money::sum((clone $moves)->where('kind', 'refund')), 'money')], 'rows' => $this->statuses(clone $projects), 'note' => 'Project counts use creation dates; payment/refund totals use posted movement dates.'];
    }

    private function procurement(array $f): array
    {
        $requests = $this->dates(Procurement::withTrashed(), 'created_at', $f);
        $moves = $this->dates(ProcurementMovement::where('status', 'posted'), 'payment_date', $f);

        return ['metrics' => [$this->metric('Requests created', (clone $requests)->count()), $this->metric('Open orders', Procurement::whereIn('status', ['approved', 'ordered'])->count()), $this->metric('Payments', Money::sum((clone $moves)->where('kind', 'payment')), 'money'), $this->metric('Refunds', Money::sum((clone $moves)->where('kind', 'refund')), 'money')], 'rows' => $this->statuses(clone $requests)];
    }

    private function operations(array $f): array
    {
        $tasks = $this->dates(Task::withTrashed(), 'created_at', $f);
        $meetings = $this->dates(Meeting::withTrashed(), 'meeting_date', $f);
        $overdue = Task::whereDate('due_on', '<', today('Asia/Karachi'))->whereNotIn('status', ['completed', 'cancelled'])->count();
        $rows = array_merge(array_map(fn ($r) => ['label' => 'Task · '.$r['label'], 'count' => $r['count']], $this->statuses(clone $tasks)), array_map(fn ($r) => ['label' => 'Meeting · '.$r['label'], 'count' => $r['count']], $this->statuses(clone $meetings)));

        return ['metrics' => [$this->metric('Tasks created', (clone $tasks)->count()), $this->metric('Tasks completed', (clone $tasks)->where('status', 'completed')->count()), $this->metric('Currently overdue', $overdue), $this->metric('Meetings in period', (clone $meetings)->count())], 'rows' => $rows];
    }

    private function organization(array $f): array
    {
        $members = $this->dates(Member::withTrashed(), 'created_at', $f);
        $users = $this->dates(User::withTrashed(), 'created_at', $f);
        $committees = $this->dates(Committee::withTrashed(), 'created_at', $f);

        return ['metrics' => [$this->metric('Members added', (clone $members)->count()), $this->metric('Active members', Member::where('status', 'active')->count()), $this->metric('Users added', (clone $users)->count()), $this->metric('Active committees', Committee::where('status', 'active')->count())], 'rows' => [['label' => 'Active users', 'count' => User::where('status', 'active')->count()], ['label' => 'Committees created', 'count' => (clone $committees)->count()]]];
    }
}
