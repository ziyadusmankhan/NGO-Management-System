<?php

namespace App\Domain\Organization\Services;

use App\Domain\Finance\Donations\Models\Donation;
use App\Domain\Finance\Expenses\Models\Expense;
use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Finance\Transactions\Models\Transaction;
use App\Domain\Procurement\Models\ProcurementMovement;
use App\Domain\Projects\Models\ProjectMovement;
use App\Domain\Security\Services\FieldEncryption;
use App\Domain\Welfare\Applications\Models\WelfareApplication;
use App\Domain\Welfare\Applications\Models\WelfareApplicationAgreement;
use App\Domain\Welfare\Assistance\Models\WelfareAssistance;
use App\Domain\Welfare\Assistance\Services\WelfareMonitoringService;
use App\Domain\Welfare\Beneficiaries\Models\Beneficiary;
use App\Domain\Welfare\Verification\Models\BeneficiaryVerification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class FinanceWelfareQuery
{
    public const DEFINITIONS = [
        'donations' => ['finance/donations/export', 'finance.donations.export', ['finance.view'], 'Donations Register'],
        'funds' => ['finance/funds/export', 'finance.funds.export', ['finance.view'], 'Funds Register'],
        'expenses' => ['finance/expenses/export', 'finance.expenses.export', ['finance.view'], 'Expenses Register'],
        'restricted-funds' => ['finance/restricted-funds/export', 'finance.restricted-funds.export', ['finance.view'], 'Restricted Funds Register'],
        'transactions' => ['finance/transactions/export', 'finance.transactions.export', ['finance.view'], 'Transaction Ledger'],
        'finance-reports' => ['finance/reports/export', 'finance.reports.export', ['finance.reports.view'], 'Finance Report'],
        'beneficiaries' => ['welfare/beneficiaries/export', 'welfare.beneficiaries.export', ['welfare.view'], 'Beneficiaries Register'],
        'applications' => ['welfare/applications/export', 'welfare.applications.export', ['welfare.assistance.view'], 'Welfare Applications Register'],
        'verification' => ['welfare/verification/export', 'welfare.verification.export', ['welfare.view'], 'Field Verification Register'],
        'assistance' => ['welfare/assistance/export', 'welfare.assistance.export', ['welfare.assistance.view'], 'Welfare Assistance Register'],
        'reviews' => ['welfare/assistance/reviews/export', 'welfare.assistance.reviews.export', ['welfare.assistance.view', 'welfare.reviews.view'], 'Reviews and Follow Ups'],
        'agreements' => ['welfare/agreements/export', 'welfare.agreements.export', ['welfare.agreements.view'], 'Welfare Agreements Register'],
    ];

    public function filters(Request $request, string $kind): array
    {
        $rules = ['search' => ['nullable', 'string', 'max:150']];
        foreach (['status', 'type', 'category', 'payment_method', 'direction', 'stage', 'priority', 'assistance_type', 'verification_status', 'approval_status', 'filter', 'sort'] as $field) {
            $rules[$field] = ['nullable', 'string', 'max:80'];
        }
        foreach (['fund', 'fund_id', 'application_id'] as $field) {
            $rules[$field] = ['nullable', 'integer', 'min:1'];
        }
        foreach (['date_from', 'date_to', 'visit_from', 'visit_to'] as $field) {
            $rules[$field] = ['nullable', 'date_format:Y-m-d'];
        }
        if ($request->filled('date_from')) {
            $rules['date_to'][] = 'after_or_equal:date_from';
        }
        if ($request->filled('visit_from')) {
            $rules['visit_to'][] = 'after_or_equal:visit_from';
        }
        if ($kind === 'verification') {
            $rules['status'][] = 'in:assigned,in_progress,completed,overdue';
            $rules['priority'][] = 'in:high,normal';
            $rules['sort'][] = 'in:newest,visit_asc,visit_desc';
        }
        if ($kind === 'reviews') {
            $rules['filter'][] = 'in:overdue,today,upcoming,none,follow_up,closure,ended';
        }

        return $request->validate($rules);
    }

    public function reviewFilters(): array
    {
        $today = app(WelfareMonitoringService::class)->today();

        return [
            'overdue' => fn ($q) => $q->whereDate('next_review_date', '<', $today->toDateString()),
            'today' => fn ($q) => $q->whereDate('next_review_date', $today->toDateString()),
            'upcoming' => fn ($q) => $q->whereBetween('next_review_date', [$today->addDay()->toDateString(), $today->addDays(30)->toDateString()]),
            'none' => fn ($q) => $q->whereNull('next_review_date'),
            'follow_up' => fn ($q) => $q->whereHas('latestReview', fn ($r) => $r->where('outcome', 'follow_up_required')),
            'closure' => fn ($q) => $q->whereHas('latestReview', fn ($r) => $r->where('outcome', 'no_longer_needed')),
            'ended' => fn ($q) => $q->whereDate('end_date', '<', $today->toDateString()),
        ];
    }

    public function query(string $kind, array $filters): Builder
    {
        $query = match ($kind) {
            'donations' => Donation::with(['fund', 'transaction']),
            'funds' => Fund::query(),
            'restricted-funds' => Fund::where(fn ($q) => $q->whereIn('type', ['restricted', 'designated'])->orWhere('restrict_spending', true))->where('status', 'active'),
            'expenses' => Expense::with(['fund', 'transaction']),
            'transactions','finance-reports' => Transaction::with('fund'),
            'beneficiaries' => Beneficiary::with(['verifiedBy', 'approvedBy']),
            'applications' => WelfareApplication::with(['beneficiary', 'fund']),
            'verification' => BeneficiaryVerification::whereNotNull('application_id')->with(['beneficiary', 'application', 'assignedTo', 'verifiedBy']),
            'assistance' => WelfareAssistance::with(['beneficiary', 'fund', 'approvedBy']),
            'reviews' => WelfareAssistance::where('status', 'approved')->with(['beneficiary', 'fund', 'latestReview.reviewedBy']),
            'agreements' => WelfareApplicationAgreement::whereHas('application')->with(['application.beneficiary', 'application.fund']),
            default => abort(404),
        };
        $search = trim($filters['search'] ?? '');
        if ($search !== '') {
            $query->where(function ($q) use ($kind, $search) {
                $columns = match ($kind) {
                    'donations' => ['donation_number', 'donor_name', 'fund_reference', 'purpose'],
                    'funds' => ['name', 'fund_reference', 'code', 'purpose'],
                    'restricted-funds' => ['name', 'fund_reference', 'purpose'],
                    'expenses' => ['expense_number', 'description', 'payee_name', 'transaction_reference', 'receipt_number'],
                    'transactions','finance-reports' => ['transaction_number', 'reference', 'description'],
                    'beneficiaries' => ['beneficiary_number', 'full_name', 'father_husband_name', 'location'],
                    'applications' => ['application_number', 'request_type'],
                    'verification' => ['verification_number'],
                    'assistance' => ['assistance_number', 'assistance_type'],
                    'reviews' => ['assistance_number'],
                    'agreements' => ['agreement_number'],
                };
                foreach ($columns as $column) {
                    $q->orWhere($column, 'like', '%'.$search.'%');
                }
                if (in_array($kind, ['expenses', 'transactions', 'finance-reports'], true)) {
                    $q->orWhereHas('fund', fn ($f) => $f->where('name', 'like', '%'.$search.'%')->orWhere('fund_reference', 'like', '%'.$search.'%'));
                }
                if (in_array($kind, ['applications', 'assistance', 'reviews', 'verification'], true)) {
                    $q->orWhereHas('beneficiary', fn ($b) => $b->where('full_name', 'like', '%'.$search.'%')->orWhere('beneficiary_number', 'like', '%'.$search.'%'));
                }
                if ($kind === 'beneficiaries') {
                    foreach (['phone', 'cnic'] as $field) {
                        $q->orWhere($field.'_lookup', app(FieldEncryption::class)->lookup($field, $search) ?? 'no-match');
                    }
                }
                if ($kind === 'verification') {
                    $q->orWhereHas('application', fn ($a) => $a->where('application_number', 'like', '%'.$search.'%'))
                        ->orWhereHas('assignedTo', fn ($u) => $u->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%'));
                }
            });
        }
        if ($kind === 'funds' && ($filters['status'] ?? '') === 'archived') {
            $query->onlyTrashed();
        }
        $fields = match ($kind) {
            'donations' => ['fund' => 'fund_id', 'payment_method' => 'payment_method', 'status' => 'status'],
            'funds' => ['type' => 'type', 'status' => 'status'],
            'restricted-funds' => [],
            'expenses' => ['category' => 'category', 'fund_id' => 'fund_id', 'status' => 'status'],
            'transactions' => ['fund_id' => 'fund_id', 'type' => 'type', 'direction' => 'direction', 'status' => 'status'],
            'finance-reports' => ['fund_id' => 'fund_id'],
            'beneficiaries' => ['category' => 'category', 'verification_status' => 'verification_status', 'approval_status' => 'approval_status'],
            'applications' => ['stage' => 'status', 'category' => 'category', 'priority' => 'priority'],
            'assistance' => ['assistance_type' => 'assistance_type', 'status' => 'status', 'fund_id' => 'fund_id'],
            'verification' => ['priority' => 'priority'],
            'reviews' => [],
            'agreements' => ['application_id' => 'application_id', 'status' => 'status'],
        };
        foreach ($fields as $input => $column) {
            if (filled($filters[$input] ?? null) && ! ($kind === 'funds' && $input === 'status' && $filters[$input] === 'archived')) {
                $query->where($column, $filters[$input]);
            }
        }
        if ($kind === 'finance-reports') {
            $query->where('status', 'posted');
        }
        $dateColumn = match ($kind) {
            'donations' => 'donation_date','expenses' => 'expense_date','transactions','finance-reports' => 'transaction_date',default => null
        };
        if ($dateColumn) {
            foreach (['date_from' => '>=', 'date_to' => '<='] as $input => $operator) {
                if (! empty($filters[$input])) {
                    $query->whereDate($dateColumn, $operator, $filters[$input]);
                }
            }
        }
        if ($kind === 'verification') {
            $status = $filters['status'] ?? null;
            if ($status === 'overdue') {
                $query->whereIn('workflow_status', ['assigned', 'in_progress'])->whereDate('visit_date', '<', now()->toDateString());
            } elseif ($status) {
                $query->where('workflow_status', $status);
            }
            foreach (['visit_from' => '>=', 'visit_to' => '<='] as $input => $operator) {
                if (! empty($filters[$input])) {
                    $query->whereDate('visit_date', $operator, $filters[$input]);
                }
            }

            return match ($filters['sort'] ?? 'newest') {
                'visit_asc' => $query->orderByRaw('visit_date is null')->orderBy('visit_date')->orderByDesc('id'),
                'visit_desc' => $query->orderByRaw('visit_date is null')->orderByDesc('visit_date')->orderByDesc('id'),
                default => $query->orderByDesc('id'),
            };
        }
        if ($kind === 'reviews') {
            $queue = $this->reviewFilters();
            if (isset($queue[$filters['filter'] ?? ''])) {
                $queue[$filters['filter']]($query);
            }

            return $query->orderByRaw('CASE WHEN next_review_date IS NULL THEN 1 ELSE 0 END')->orderBy('next_review_date')->orderBy('id');
        }

        return match ($kind) {
            'donations' => $query->orderByDesc('donation_date')->orderByDesc('id'),
            'expenses' => $query->orderByDesc('expense_date')->orderByDesc('id'),
            'transactions' => $query->orderBy('transaction_date')->orderBy('id'),
            'finance-reports' => $query->orderByDesc('transaction_date')->orderByDesc('id'),
            'applications' => $query->orderByDesc('submitted_at')->orderByDesc('id'),
            'restricted-funds' => $query->orderBy('name')->orderBy('id'),
            default => $query->orderByDesc('id'),
        };
    }

    public function projectExpenses(array $filters): Builder
    {
        return $this->controlledExpenses(ProjectMovement::where('kind', 'payment')->with(['project', 'transaction']), $filters, 'project', 'project')
            ->when(! empty($filters['search']), fn ($q) => $q->where(fn ($q) => $q->where('payee', 'like', '%'.$filters['search'].'%')->orWhereHas('project', fn ($p) => $p->where('number', 'like', '%'.$filters['search'].'%')->orWhere('name', 'like', '%'.$filters['search'].'%'))));
    }

    public function officeExpenses(array $filters): Builder
    {
        return $this->controlledExpenses(ProcurementMovement::whereNull('source_type')->where('kind', 'payment')->with(['procurement', 'transaction']), $filters, 'admin', 'procurement')
            ->when(! empty($filters['search']), fn ($q) => $q->whereHas('procurement', fn ($p) => $p->where('number', 'like', '%'.$filters['search'].'%')));
    }

    private function controlledExpenses(Builder $query, array $filters, string $category, string $parent): Builder
    {
        if (! empty($filters['category']) && $filters['category'] !== $category) {
            $query->whereRaw('1=0');
        }
        if (! empty($filters['fund_id'])) {
            $query->where('fund_id', $filters['fund_id']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status'] === 'paid' ? 'posted' : $filters['status']);
        }
        foreach (['date_from' => '>=', 'date_to' => '<='] as $input => $operator) {
            if (! empty($filters[$input])) {
                $query->whereDate('payment_date', $operator, $filters[$input]);
            }
        }

        return $query->orderByDesc('id');
    }
}
