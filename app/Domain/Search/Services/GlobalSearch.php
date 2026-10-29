<?php

namespace App\Domain\Search\Services;

use App\Domain\Business\Models\BusinessApplication;
use App\Domain\Committees\Models\Committee;
use App\Domain\Documents\Services\DocumentAccess;
use App\Domain\Finance\Donations\Models\Donation;
use App\Domain\Finance\Expenses\Models\Expense;
use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Finance\Transactions\Models\Transaction;
use App\Domain\Members\Models\Member;
use App\Domain\Operations\Models\Meeting;
use App\Domain\Operations\Models\Task;
use App\Domain\Procurement\Services\ProcurementReportService;
use App\Domain\Projects\Models\Project;
use App\Domain\Qard\Models\QardLoan;
use App\Domain\Reports\Models\ReportRun;
use App\Domain\Reports\Services\ReportBuilder;
use App\Domain\Security\Services\FieldEncryption;
use App\Domain\Users\Models\User;
use App\Domain\Welfare\Applications\Models\WelfareApplication;
use App\Domain\Welfare\Assistance\Models\WelfareAssistance;
use App\Domain\Welfare\Beneficiaries\Models\Beneficiary;
use Illuminate\Support\Collection;

class GlobalSearch
{
    public function run(User $user, string $term): Collection
    {
        $term = trim($term);
        if (mb_strlen($term) < 2) {
            return collect();
        }
        $searchable = $this->escapeLike($term);
        if (trim($searchable) === '') {
            return collect();
        }
        $like = '%'.$searchable.'%';
        $groups = collect();

        if ($user->hasPermission('members.view')) {
            $lookup = app(FieldEncryption::class);
            $rows = Member::query()->where(function ($query) use ($like, $term, $lookup) {
                $query->where('member_number', 'like', $like)->orWhere('first_name', 'like', $like)->orWhere('last_name', 'like', $like)
                    ->orWhere('cnic_lookup', $lookup->lookup('cnic', $term) ?? 'no-match')->orWhere('phone_lookup', $lookup->lookup('phone', $term) ?? 'no-match');
            })->limit(6)->get()->map(fn ($member) => $this->result('Member', $member->member_number, $member->full_name, route('members.show', $member), $member->status));
            $this->add($groups, 'Members', $rows);
        }

        if ($user->hasPermission('committees.view')) {
            $rows = Committee::where(fn ($query) => $query->where('committee_number', 'like', $like)->orWhere('name', 'like', $like))->limit(6)->get()
                ->map(fn ($committee) => $this->result('Committee', $committee->committee_number, $committee->name, route('committees.show', $committee), $committee->status));
            $this->add($groups, 'Committees', $rows);
        }

        if ($user->hasPermission('users.view')) {
            $rows = User::where(fn ($query) => $query->where('name', 'like', $like)->orWhere('email', 'like', $like))->limit(6)->get()
                ->map(fn ($account) => $this->result('User', $account->email, $account->name, route('users.show', $account), $account->status));
            $this->add($groups, 'Users', $rows);
        }

        if ($user->hasPermission('welfare.view')) {
            $lookup = app(FieldEncryption::class);
            $rows = Beneficiary::where(function ($query) use ($like, $term, $lookup) {
                $query->where('beneficiary_number', 'like', $like)->orWhere('full_name', 'like', $like)
                    ->orWhere('cnic_lookup', $lookup->lookup('cnic', $term) ?? 'no-match')->orWhere('phone_lookup', $lookup->lookup('phone', $term) ?? 'no-match');
            })->limit(6)->get()->map(fn ($person) => $this->result('Beneficiary', $person->beneficiary_number, $person->full_name, route('welfare.beneficiaries.show', $person), $person->approval_status));
            $this->add($groups, 'Beneficiaries', $rows);
        }

        if ($user->hasPermission('welfare.assistance.view')) {
            $applications = WelfareApplication::with('beneficiary')->where(fn ($query) => $query->where('application_number', 'like', $like)->orWhereHas('beneficiary', fn ($beneficiary) => $beneficiary->where('full_name', 'like', $like)->orWhere('beneficiary_number', 'like', $like)))->limit(6)->get()
                ->map(fn ($application) => $this->result('Welfare application', $application->application_number, $application->beneficiary?->full_name ?? 'Unknown beneficiary', route('welfare.applications.show', $application), $application->status));
            $assistances = WelfareAssistance::with('beneficiary')->where(fn ($query) => $query->where('assistance_number', 'like', $like)->orWhereHas('beneficiary', fn ($beneficiary) => $beneficiary->where('full_name', 'like', $like)->orWhere('beneficiary_number', 'like', $like)))->limit(6)->get()
                ->map(fn ($assistance) => $this->result('Welfare assistance', $assistance->assistance_number, $assistance->beneficiary?->full_name ?? 'Unknown beneficiary', route('welfare.assistance.show', $assistance), $assistance->status));
            $this->add($groups, 'Welfare', $applications->concat($assistances)->take(8));
        }

        if ($user->hasPermission('finance.view')) {
            $funds = Fund::where(fn ($query) => $query->where('fund_reference', 'like', $like)->orWhere('name', 'like', $like)->orWhere('code', 'like', $like))->limit(6)->get()
                ->map(fn ($fund) => $this->result('Fund', $fund->fund_reference, $fund->name, route('finance.funds.show', $fund), $fund->status));
            $donations = Donation::where('donation_number', 'like', $like)->limit(4)->get()->map(fn ($donation) => $this->result('Donation', $donation->donation_number, 'PKR '.$donation->amount, route('finance.donations.show', $donation), $donation->status));
            $expenses = Expense::where('expense_number', 'like', $like)->limit(4)->get()->map(fn ($expense) => $this->result('Expense', $expense->expense_number, 'PKR '.$expense->amount, route('finance.expenses.show', $expense), $expense->status));
            $transactions = Transaction::where('transaction_number', 'like', $like)->limit(4)->get()->map(fn ($transaction) => $this->result('Transaction', $transaction->transaction_number, 'PKR '.$transaction->amount, route('finance.transactions.show', $transaction), $transaction->status));
            $this->add($groups, 'Finance', $funds->concat($donations)->concat($expenses)->concat($transactions)->take(10));
        }

        if ($user->hasPermission('qard.view')) {
            $rows = QardLoan::with('beneficiary')->where(fn ($query) => $query->where('number', 'like', $like)->orWhereHas('beneficiary', fn ($beneficiary) => $beneficiary->where('full_name', 'like', $like)))->limit(6)->get()
                ->map(fn ($loan) => $this->result('Qard loan', $loan->number, $loan->beneficiary?->full_name ?? 'Unknown beneficiary', route('qard.show', $loan), $loan->status));
            $this->add($groups, 'Qard-e-Hasana', $rows);
        }

        if ($user->hasPermission('business.view')) {
            $rows = BusinessApplication::with('beneficiary')->where(fn ($query) => $query->where('number', 'like', $like)->orWhere('name', 'like', $like)->orWhereHas('beneficiary', fn ($beneficiary) => $beneficiary->where('full_name', 'like', $like)))->limit(6)->get()
                ->map(fn ($application) => $this->result('Business case', $application->number, $application->name, route('business.applications.show', $application), $application->status));
            $this->add($groups, 'Small Business', $rows);
        }

        if ($user->hasPermission('projects.view')) {
            $rows = Project::where(fn ($query) => $query->where('number', 'like', $like)->orWhere('name', 'like', $like))->limit(6)->get()
                ->map(fn ($project) => $this->result('Project', $project->number, $project->name, route('projects.show', $project), $project->status));
            $this->add($groups, 'Projects', $rows);
        }

        if ($user->hasPermission('procurement.view')) {
            $rows = app(ProcurementReportService::class)->visible($user)->where('number', 'like', $like)->limit(6)->get()
                ->map(fn ($procurement) => $this->result('Procurement', $procurement->number, str($procurement->parent_type)->headline(), route('operations.procurement.show', $procurement), $procurement->status));
            $this->add($groups, 'Procurement', $rows);
        }

        if ($user->hasPermission('tasks.view')) {
            $rows = Task::where(fn ($query) => $query->where('number', 'like', $like)->orWhere('title', 'like', $like))->limit(6)->get()
                ->map(fn ($task) => $this->result('Task', $task->number, $task->title, route('operations.tasks.show', $task), $task->status));
            $this->add($groups, 'Tasks', $rows);
        }

        if ($user->hasPermission('meetings.view')) {
            $rows = Meeting::where(fn ($query) => $query->where('number', 'like', $like)->orWhere('title', 'like', $like))->limit(6)->get()
                ->map(fn ($meeting) => $this->result('Meeting', $meeting->number, $meeting->title, route('operations.meetings.show', $meeting), $meeting->status));
            $this->add($groups, 'Meetings', $rows);
        }

        if ($user->hasPermission('documents.view')) {
            $rows = app(DocumentAccess::class)->visible($user)->where(fn ($query) => $query->where('number', 'like', $like)->orWhere('title', 'like', $like)->orWhere('parent_reference', 'like', $like))->limit(6)->get()
                ->map(fn ($document) => $this->result('Document', $document->number, $document->title, route('records.documents.show', $document), $document->archived_at ? 'archived' : 'active'));
            $this->add($groups, 'Documents', $rows);
        }

        if ($user->hasPermission('reports.view')) {
            $types = array_keys(app(ReportBuilder::class)->allowed($user));
            $rows = ReportRun::whereIn('type', $types)->where(fn ($query) => $query->where('number', 'like', $like)->orWhere('title', 'like', $like))->limit(6)->get()
                ->map(fn ($report) => $this->result('Report', $report->number, $report->title, route('records.reports.show', $report), 'generated'));
            $this->add($groups, 'Reports', $rows);
        }

        return $groups;
    }

    private function escapeLike(string $term): string
    {
        // Wildcards are not part of the public search language. Removing them
        // prevents a query such as "%" from enumerating every authorized row.
        return str_replace(['%', '_'], '', $term);
    }

    private function add(Collection $groups, string $label, Collection $rows): void
    {
        if ($rows->isNotEmpty()) {
            $groups->push(['label' => $label, 'results' => $rows->values()]);
        }
    }

    private function result(string $type, ?string $reference, string $title, string $url, ?string $status): array
    {
        return compact('type', 'reference', 'title', 'url', 'status');
    }
}
