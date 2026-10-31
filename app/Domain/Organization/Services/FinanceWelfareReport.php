<?php

namespace App\Domain\Organization\Services;

use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Finance\Funds\Services\FundBalanceService;
use App\Domain\Finance\Funds\Services\FundReportingService;
use App\Domain\Finance\Support\Money;
use App\Domain\Users\Models\User;
use App\Domain\Welfare\Assistance\Models\WelfareAssistancePayment;
use App\Domain\Welfare\Assistance\Services\WelfareAssistancePaymentService;
use App\Domain\Welfare\Assistance\Services\WelfareMonitoringService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class FinanceWelfareReport
{
    public function build(string $kind, array $filters, User $actor): array
    {
        $query = app(FinanceWelfareQuery::class);
        $records = $this->records($query->query($kind, $filters));
        $report = ['kind' => $kind, 'title' => FinanceWelfareQuery::DEFINITIONS[$kind][3], 'count' => $records->count(),
            'generated_at' => now('Asia/Karachi'), 'prepared_by' => $actor->name, 'active' => 0, 'inactive' => 0,
            'scope' => $this->scope($filters), 'note' => 'All matching records across every page. Amounts are in PKR.',
            'template' => config('organization.financial_export_template')];
        $sections = [];
        $metrics = [$this->metric('Records', $records->count())];
        switch ($kind) {
            case 'donations':
                $sections[] = $this->section('Donations', ['Donation', 'Date', 'Donor', 'Fund', 'Amount (PKR)', 'Designation', 'Method', 'Status'],
                    ['text', 'date', 'text', 'text', 'money', 'text', 'text', 'text'], [22, 19, 33, 37, 24, 23, 24, 20],
                    $records->map(fn ($d) => [$d->donation_number, $d->donation_date, $d->donor_type === 'anonymous' ? 'Anonymous' : $d->donor_name, $this->fund($d->fund), $d->amount, $this->label($d->designation), $this->label($d->payment_method), $this->label($d->status)])->all());
                $posted = $records->filter(fn ($d) => $d->transaction?->status === 'posted' && $d->transaction?->direction === 'credit');
                $metrics[] = $this->metric('Recorded amount (PKR)', Money::total($records->pluck('amount')), 'money');
                $metrics[] = $this->metric('Posted credits (PKR)', Money::total($posted->map(fn ($d) => $d->transaction->amount)), 'money');
                $metrics[] = $this->metric('Posted donations', $posted->count());
                $report['note'] .= ' Recorded donations and posted ledger credits are shown separately.';
                break;
            case 'funds': case 'restricted-funds':
                $service = app(FundReportingService::class);
                $records->each(fn ($f) => $f->financial_summary = $service->summary($f));
                $sections[] = $this->fundSection($records);
                $sections[] = $this->section('Fund Movement Breakdown', ['Fund', 'Received (PKR)', 'Spent (PKR)', 'Recoveries (PKR)', 'Refunds in (PKR)', 'Refunds out (PKR)', 'Opening (PKR)', 'Ledger (PKR)'],
                    ['text', 'money', 'money', 'money', 'money', 'money', 'money', 'money'], [46, 24, 24, 24, 24, 24, 24, 24],
                    $records->map(fn ($f) => [$this->fund($f), ...array_map(fn ($k) => $f->financial_summary[$k], ['received', 'spent', 'recovered', 'refunded', 'refunds_paid', 'opening_balance', 'balance'])])->all());
                $totals = $service->totals($records);
                foreach (['Ledger cash' => 'balance', 'Unpaid commitments' => 'allocated', 'After commitments' => 'available'] as $label => $key) {
                    $metrics[] = $this->metric($label.' (PKR)', $totals[$key], 'money');
                }
                $report['note'] .= ' Ledger cash is separate from approved unpaid commitments. Available means cash after commitments; negative values remain visible. Recoveries and refunds are counted once.';
                break;
            case 'expenses':
                $project = $this->records($query->projectExpenses($filters));
                $office = $this->records($query->officeExpenses($filters));
                $all = $records->concat($project)->concat($office);
                if ($all->count() > DirectoryReport::LIMIT) {
                    $this->tooLarge();
                }
                $headers = ['Expense / payment', 'Date', 'Payee / parent', 'Category', 'Fund', 'Amount (PKR)', 'Status', 'Ledger reference'];
                $types = ['text', 'date', 'text', 'text', 'text', 'money', 'text', 'text'];
                $widths = [25, 19, 40, 21, 35, 25, 18, 26];
                $sections[] = $this->section('Expenses', $headers, $types, $widths, $records->map(fn ($e) => [$e->expense_number, $e->expense_date, $e->payee_name, $this->label($e->category), $this->fund($e->fund), $e->amount, $this->label($e->status), $e->transaction?->transaction_number])->all());
                foreach (['Project Payments' => $project, 'Office Procurement Payments' => $office] as $title => $rows) {
                    $sections[] = $this->section($title, $headers, $types, $widths, $rows->map(function ($m) use ($title) {
                        $parent = $title === 'Project Payments' ? $m->project : $m->procurement;
                        $fund = Fund::withTrashed()->find($m->fund_id);

                        return [$m->number ?? ($parent?->number.' / #'.$m->id), $m->payment_date, trim(($m->payee ?? '')."\n".$parent?->number), $title === 'Project Payments' ? 'Project' : 'Office procurement', $this->fund($fund), $m->amount, $this->label($m->status), $m->transaction?->transaction_number];
                    })->all());
                }
                $report['count'] = $all->count();
                $metrics = [$this->metric('Source records', $all->count())];
                $metrics[] = $this->metric('Recorded amount (PKR)', Money::total($all->pluck('amount')), 'money');
                $posted = $all->filter(fn ($m) => $m->transaction?->status === 'posted' && $m->transaction?->direction === 'debit');
                $metrics[] = $this->metric('Posted debits (PKR)', Money::total($posted->map(fn ($m) => $m->transaction->amount)), 'money');
                $metrics[] = $this->metric('Posted movements', $posted->count());
                $report['note'] .= ' Generic requests, project payments and office procurement payments are separate source groups. Linked procurement payments appear through their parent movement once. Requested amounts include pending/void records; posted totals include only authoritative posted debits.';
                break;
            case 'transactions': case 'finance-reports':
                $sections[] = $this->transactions($records, $kind === 'transactions');
                $posted = $records->where('status', 'posted');
                $credits = Money::total($posted->where('direction', 'credit')->pluck('amount'));
                $debits = Money::total($posted->where('direction', 'debit')->pluck('amount'));
                $metrics[] = $this->metric('Posted credits (PKR)', $credits, 'money');
                $metrics[] = $this->metric('Posted debits (PKR)', $debits, 'money');
                $metrics[] = $this->metric('Net movement (PKR)', Money::decimal(Money::add(Money::minor($credits), -Money::minor($debits))), 'money');
                if ($kind === 'finance-reports') {
                    $funds = $this->records(Fund::query()->when(! empty($filters['fund_id']), fn ($q) => $q->whereKey($filters['fund_id']), fn ($q) => $q->where('status', 'active'))->orderBy('name'));
                    $funds->each(fn ($f) => $f->financial_summary = app(FundReportingService::class)->summary($f));
                    $sections[] = $this->fundSection($funds, 'Current Fund Balances');
                    $report['note'] .= ' Transaction totals use the selected date range. The fund balance section is current as of export, including opening balances and commitments; it is not a historical closing balance. All matching posted transactions are included, beyond the screen preview.';
                } else {
                    $report['note'] .= ' Running balances are actual per-fund ledger balances at each transaction, including earlier movements outside the selected filters. Pending/void transactions do not change cash.';
                }
                break;
            case 'beneficiaries':
                $sections[] = $this->section('Beneficiaries', ['Beneficiary', 'Full name', 'Phone', 'Location', 'Category', 'Verification', 'Approval', 'Registered'],
                    ['text', 'text', 'text', 'text', 'text', 'text', 'text', 'date'], [23, 35, 24, 30, 24, 24, 24, 21],
                    $records->map(fn ($b) => [$b->beneficiary_number, $b->full_name, $b->phone, $b->location, $this->label($b->category), $this->label($b->verification_status), $this->label($b->approval_status), $b->created_at])->all());
                $metrics[] = $this->metric('Verified', $records->where('verification_status', 'verified')->count());
                $metrics[] = $this->metric('Approved', $records->where('approval_status', 'approved')->count());
                $metrics[] = $this->metric('Pending verification', $records->where('verification_status', 'pending')->count());
                $report['note'] = 'Eligible beneficiary metadata only. Identification numbers, household narratives and private evidence are excluded.';
                break;
            case 'applications':
                $sections[] = $this->section('Applications', ['Application', 'Beneficiary', 'Fund', 'Category / request', 'Requested (PKR)', 'Recommended (PKR)', 'Stage', 'Submitted'],
                    ['text', 'text', 'text', 'text', 'money', 'money', 'text', 'date'], [23, 32, 34, 33, 24, 25, 32, 21],
                    $records->map(fn ($a) => [$a->application_number, $a->beneficiary?->full_name, $this->fund($a->fund), $this->label($a->category)."\n".$this->label($a->request_type), $a->requested_amount, $a->recommended_amount, $this->label($a->status), $a->submitted_at])->all());
                $metrics[] = $this->metric('Requested (PKR)', Money::total($records->pluck('requested_amount')->filter(fn ($v) => $v !== null)), 'money');
                $metrics[] = $this->metric('Approved applications', $records->where('status', 'approved')->count());
                $metrics[] = $this->metric('Rejected applications', $records->where('status', 'rejected')->count());
                break;
            case 'verification':
                $sections[] = $this->section('Field Verification', ['Verification', 'Application', 'Beneficiary', 'Assigned officer', 'Priority', 'Visit date', 'Workflow status', 'Completed'],
                    ['text', 'text', 'text', 'text', 'text', 'date', 'text', 'date'], [24, 23, 35, 33, 18, 23, 25, 23],
                    $records->map(fn ($v) => [$v->verification_number, $v->application?->application_number, $v->beneficiary?->full_name, $v->assignedTo?->name, $this->label($v->priority), $v->visit_date, $this->label($v->workflow_status), $v->completed_at])->all());
                $metrics[] = $this->metric('Assigned', $records->where('workflow_status', 'assigned')->count());
                $metrics[] = $this->metric('In progress', $records->where('workflow_status', 'in_progress')->count());
                $metrics[] = $this->metric('Completed', $records->where('workflow_status', 'completed')->count());
                $report['note'] = 'Filtered verification queue. Private findings, medical/household details and evidence files are excluded.';
                break;
            case 'assistance':
                $sections[] = $this->section('Assistance', ['Assistance', 'Beneficiary', 'Fund', 'Approved (PKR)', 'Posted paid (PKR)', 'Remaining (PKR)', 'Frequency', 'Status'],
                    ['text', 'text', 'text', 'money', 'money', 'money', 'text', 'text'], [24, 35, 35, 25, 25, 25, 21, 23],
                    $records->map(function ($a) {
                        $service = app(WelfareAssistancePaymentService::class);
                        $paid = $service->paidMinor($a);

                        return [$a->assistance_number, $a->beneficiary?->full_name, $this->fund($a->fund), $a->amount, Money::decimal($paid), Money::decimal($service->remainingMinor($a, $paid)), $this->label($a->frequency), $this->label($a->status)];
                    })->all());
                $paymentQuery = WelfareAssistancePayment::whereIn('assistance_id', $records->pluck('id'))->with(['assistance', 'transaction'])->orderBy('payment_date')->orderBy('id');
                $payments = $this->records($paymentQuery);
                $sections[] = $this->section('Assistance Payments', ['Payment', 'Assistance', 'Date', 'Amount (PKR)', 'Method', 'Receipt / reference', 'Status', 'Ledger'],
                    ['text', 'text', 'date', 'money', 'text', 'text', 'text', 'text'], [25, 25, 23, 25, 24, 35, 22, 27],
                    $payments->map(fn ($p) => [$p->payment_number, $p->assistance?->assistance_number, $p->payment_date, $p->amount, $this->label($p->payment_method), $p->receipt_number ?: $p->transaction_reference, $this->label($p->status), $p->transaction?->transaction_number])->all());
                $metrics[] = $this->metric('Approved cases', $records->where('status', 'approved')->count());
                $metrics[] = $this->metric('Approved limits (PKR)', Money::total($records->pluck('amount')), 'money');
                $metrics[] = $this->metric('Posted paid (PKR)', Money::total($payments->where('status', 'posted')->pluck('amount')), 'money');
                $report['note'] .= ' Remaining authority follows the assistance payment service. Payment history retains voids; posted totals exclude them.';
                break;
            case 'reviews':
                $sections[] = $this->section('Reviews and Follow Ups', ['Assistance', 'Beneficiary', 'Fund', 'Next review', 'Period end', 'Last review', 'Outcome', 'Reviewed by'],
                    ['text', 'text', 'text', 'date', 'date', 'date', 'text', 'text'], [24, 32, 33, 23, 23, 23, 33, 30],
                    $records->map(fn ($a) => [$a->assistance_number, $a->beneficiary?->full_name, $this->fund($a->fund), $a->next_review_date, $a->end_date, $a->latestReview?->review_date, $this->label($a->latestReview?->outcome), $a->latestReview?->reviewedBy?->name])->all());
                $today = app(WelfareMonitoringService::class)->today()->toDateString();
                $metrics[] = $this->metric('Overdue', $records->filter(fn ($a) => $a->next_review_date && $a->next_review_date->toDateString() < $today)->count());
                $metrics[] = $this->metric('Due today', $records->filter(fn ($a) => $a->next_review_date?->toDateString() === $today)->count());
                $metrics[] = $this->metric('No review date', $records->whereNull('next_review_date')->count());
                $report['note'] = 'Current approved-assistance review queue in Asia/Karachi time. The selected filter applies to queue records; private review notes are excluded.';
                break;
            case 'agreements':
                $sections[] = $this->section('Agreements', ['Agreement', 'Application', 'Beneficiary', 'Fund', 'Approved (PKR)', 'Status', 'Prepared', 'Fully signed'],
                    ['text', 'text', 'text', 'text', 'money', 'text', 'date', 'date'], [25, 25, 34, 35, 25, 21, 23, 23],
                    $records->map(fn ($a) => [$a->agreement_number, $a->application?->application_number, $a->application?->beneficiary?->full_name, $this->fund($a->application?->fund), $a->approved_amount, $this->label($a->status), $a->prepared_at, $a->fully_signed_at])->all());
                $metrics[] = $this->metric('Approved amounts (PKR)', Money::total($records->pluck('approved_amount')->filter(fn ($v) => $v !== null)), 'money');
                $metrics[] = $this->metric('Signed', $records->where('status', 'signed')->count());
                $metrics[] = $this->metric('Prepared', $records->where('status', 'prepared')->count());
                $report['note'] = 'Agreement register only. Original signed copies and the printable agreement remain available through their existing authorized workflow.';
                break;
        }
        if (array_sum(array_map(fn ($s) => count($s['rows']), $sections)) > 10000) {
            $this->tooLarge();
        }
        $report['sections'] = $sections;
        $last = max(16, 15 + count($sections[0]['rows']));
        if ($kind !== 'expenses') {
            $metrics[0]['formula'] = '=COUNTA(B16:B'.$last.')';
        }
        $sum = fn (string $column) => '=ROUND(SUM('.$column.'16:'.$column.$last.'),2)';
        if ($kind === 'donations') {
            $metrics[1]['formula'] = $sum('F');
        }
        if (in_array($kind, ['funds', 'restricted-funds'], true)) {
            foreach ([1 => 'F', 2 => 'G', 3 => 'H'] as $index => $column) {
                $metrics[$index]['formula'] = $sum($column);
            }
        }
        if ($kind === 'applications') {
            $metrics[1]['formula'] = $sum('F');
        }
        if ($kind === 'agreements') {
            $metrics[1]['formula'] = $sum('F');
        }
        if ($kind === 'assistance') {
            $metrics[2]['formula'] = $sum('E');
            $metrics[3]['formula'] = $sum('F');
        }
        if (in_array($kind, ['transactions', 'finance-reports'], true)) {
            $metrics[1]['formula'] = '=ROUND(SUMIFS(G16:G'.$last.',H16:H'.$last.',"Posted",F16:F'.$last.',"Credit"),2)';
            $metrics[2]['formula'] = '=ROUND(SUMIFS(G16:G'.$last.',H16:H'.$last.',"Posted",F16:F'.$last.',"Debit"),2)';
            $metrics[3]['formula'] = '=ROUND(D6-F6,2)';
        }
        if ($kind === 'expenses') {
            $counts = [];
            $amounts = [];
            foreach ($sections as $index => $section) {
                $first = $index === 0 ? 16 : 8;
                $end = max($first, $first + count($section['rows']) - 1);
                $sheet = "'".str_replace("'", "''", $section['title'])."'!";
                $counts[] = 'COUNTA('.$sheet.'B'.$first.':B'.$end.')';
                $amounts[] = 'SUM('.$sheet.'G'.$first.':G'.$end.')';
            }
            $metrics[0]['formula'] = '='.implode('+', $counts);
            $metrics[1]['formula'] = '=ROUND('.implode('+', $amounts).',2)';
        }
        $report['metrics'] = $metrics;

        return $report;
    }

    private function records(Builder $query): Collection
    {
        if ((clone $query)->count() > DirectoryReport::LIMIT) {
            $this->tooLarge();
        }

        return $query->get();
    }

    private function tooLarge(): never
    {
        throw ValidationException::withMessages(['export' => 'Choose narrower filters. Exports support up to 2,500 source records and 10,000 detail rows without truncation.']);
    }

    private function metric(string $label, mixed $value, string $type = 'number'): array
    {
        return compact('label', 'value', 'type');
    }

    private function label(?string $value): string
    {
        return $value === null ? '' : str($value)->headline()->toString();
    }

    private function fund(?Fund $fund): string
    {
        return $fund ? $fund->name."\n".$fund->fund_reference : '';
    }

    private function section(string $title, array $headers, array $types, array $widths, array $rows): array
    {
        return compact('title', 'headers', 'types', 'widths', 'rows');
    }

    private function fundSection(Collection $funds, string $title = 'Funds'): array
    {
        return $this->section($title, ['Fund', 'Type', 'Status', 'Opening (PKR)', 'Ledger (PKR)', 'Allocated (PKR)', 'Available (PKR)', 'Purpose'],
            ['text', 'text', 'text', 'money', 'money', 'money', 'money', 'text'], [43, 21, 21, 24, 24, 24, 24, 35],
            $funds->map(fn ($f) => [$this->fund($f), $this->label($f->type), $f->trashed() ? 'Archived' : $this->label($f->status), $f->financial_summary['opening_balance'], $f->financial_summary['balance'], $f->financial_summary['allocated'], $f->financial_summary['available'], $this->label($f->purpose)])->all());
    }

    private function transactions(Collection $rows, bool $running): array
    {
        return $this->section($running ? 'Transactions' : 'Posted Transactions', ['Transaction / reference', 'Date', 'Fund', 'Type', 'Direction', 'Amount (PKR)', 'Status', $running ? 'Fund balance (PKR)' : 'Source'],
            ['text', 'date', 'text', 'text', 'text', 'money', 'text', $running ? 'money' : 'text'], [33, 21, 35, 24, 20, 24, 20, 36],
            $rows->map(fn ($t) => [trim($t->transaction_number."\n".$t->reference), $t->transaction_date, $this->fund($t->fund), $this->label($t->type), $this->label($t->direction), $t->amount, $this->label($t->status),
                $running ? ($t->fund ? app(FundBalanceService::class)->balanceAfterTransaction($t) : null) : ($t->source_type ? class_basename($t->source_type).' #'.$t->source_id : '')])->all());
    }

    private function scope(array $filters): string
    {
        $parts = [];
        foreach ($filters as $key => $value) {
            if (! filled($value) || in_array($key, ['sort'], true)) {
                continue;
            }
            $parts[] = $key === 'search' ? 'Search applied' : $this->label($key).': '.(in_array($key, ['date_from', 'date_to', 'visit_from', 'visit_to'], true) ? $value : $this->label((string) $value));
        }

        return $parts ? implode(' / ', $parts) : 'All matching records';
    }
}
