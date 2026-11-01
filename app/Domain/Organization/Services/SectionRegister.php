<?php

namespace App\Domain\Organization\Services;

use App\Domain\Audit\Models\AuditEvent;
use App\Domain\Audit\Services\AuditQuery;
use App\Domain\Business\Models\BusinessApplication;
use App\Domain\Business\Services\BusinessReportService;
use App\Domain\Documents\Services\DocumentAccess;
use App\Domain\Finance\Support\Money;
use App\Domain\Operations\Models\Meeting;
use App\Domain\Operations\Models\Task;
use App\Domain\Procurement\Services\ProcurementReportService;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Services\ProjectReportService;
use App\Domain\Qard\Models\QardLoan;
use App\Domain\Qard\Services\QardPaymentService;
use App\Domain\Reports\Models\ReportRun;
use App\Domain\Reports\Services\ReportBuilder;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SectionRegister
{
    public const TYPES = [
        'qard' => ['Qard-e-Hasana Register', ['qard.view']],
        'business' => ['Small Business Register', ['business.view']],
        'projects' => ['Projects Register', ['projects.view']],
        'tasks' => ['Tasks Register', ['tasks.view']],
        'meetings' => ['Meetings Register', ['meetings.view']],
        'procurement' => ['Procurement Register', ['procurement.view']],
        'documents' => ['Documents Register', ['documents.view']],
        'reports' => ['Reports Register', ['reports.view', 'reports.generate']],
        'audit' => ['Audit Trail', ['audit.view', 'audit.export']],
        'project-report' => ['Project Financial Report', ['projects.view', 'projects.reports']],
        'business-report' => ['Small Business Financial Report', ['business.view', 'business.reports']],
        'procurement-report' => ['Procurement Financial Report', ['procurement.view', 'procurement.reports']],
    ];

    public function build(string $kind, Request $request): array
    {
        abort_unless(isset(self::TYPES[$kind]), 404);
        foreach (self::TYPES[$kind][1] as $permission) {
            abort_unless($request->user()->hasPermission($permission), 403);
        }
        $rules = [];
        foreach (['search', 'status', 'support_type', 'priority', 'type', 'category', 'module', 'reference', 'action', 'outcome', 'historical'] as $key) {
            $rules[$key] = 'nullable|string|max:150';
        }
        foreach (['assigned_user_id', 'committee_id', 'uploader', 'actor_id', 'report_id', 'fund_id', 'category_id', 'responsible_id', 'supplier_id', 'parent_id'] as $key) {
            $rules[$key] = 'nullable|integer|min:1';
        }
        $rules['parent_type'] = 'nullable|in:office,project,business';
        foreach (['archived', 'overdue', 'reminders'] as $key) {
            $rules[$key] = 'nullable|boolean';
        }
        $rules['from'] = 'nullable|date_format:Y-m-d';
        $rules['to'] = ['nullable', 'date_format:Y-m-d', ...($request->filled('from') ? ['after_or_equal:from'] : [])];
        $f = $request->validate($rules);
        $actor = $request->user();
        $sections = [];
        $snapshot = null;
        if (str_ends_with($kind, '-report')) {
            return $this->financial($kind, $f, $actor);
        }
        $q = match ($kind) {
            'qard' => QardLoan::with(['beneficiary', 'fund', 'reminder']),
            'business' => BusinessApplication::with(['beneficiary', 'fund', 'reminder']),
            'projects' => Project::with(['fund', 'reminder']),
            'tasks' => Task::with(['assignee', 'committee', 'project']),
            'meetings' => Meeting::with(['chair', 'committee'])->withCount(['attendees', 'tasks']),
            'procurement' => app(ProcurementReportService::class)->visible($actor)->with('fund'),
            'documents' => app(DocumentAccess::class)->visible($actor)->with('versions'),
            'reports' => ReportRun::with('generator')->whereIn('type', array_keys(app(ReportBuilder::class)->allowed($actor))),
            'audit' => app(AuditQuery::class)->query($f, $snapshot = (int) AuditEvent::max('id')),
        };
        if (in_array($kind, ['business', 'projects']) && ! empty($f['archived'])) {
            $q->onlyTrashed();
        }
        if (in_array($kind, ['tasks', 'meetings']) && ! empty($f['archived'])) {
            $q->withTrashed();
        }
        if ($kind === 'procurement' && empty($f['archived'])) {
            $q->whereNull('deleted_at');
        }
        if (! in_array($kind, ['audit', 'documents', 'reports'])) {
            foreach (['status', 'support_type', 'priority', 'assigned_user_id', 'type', 'committee_id'] as $key) {
                $allowed = match ($kind) {
                    'business' => ['status', 'support_type'],'tasks' => ['status', 'priority', 'assigned_user_id'],'meetings' => ['status', 'type', 'committee_id'],default => ['status']
                };
                if (in_array($key, $allowed) && ! empty($f[$key])) {
                    $q->where($key, $f[$key]);
                }
            }
            if (! empty($f['search'])) {
                $q->where(function ($q) use ($f, $kind) {
                    $s = '%'.$f['search'].'%';
                    $q->where('number', 'like', $s);
                    if (in_array($kind, ['business', 'projects'])) {
                        $q->orWhere('name', 'like', $s);
                    }if (in_array($kind, ['tasks', 'meetings'])) {
                        $q->orWhere('title', 'like', $s);
                    }if (in_array($kind, ['qard', 'business'])) {
                        $q->orWhereHas('beneficiary', fn ($b) => $b->where('full_name', 'like', $s));
                    }
                });
            }
        }
        if (in_array($kind, ['qard', 'business', 'projects']) && ! empty($f['reminders'])) {
            $q->whereHas('reminder', fn ($r) => $r->whereNull('resolved_at'));
        }
        if ($kind === 'tasks' && ! empty($f['overdue'])) {
            $q->whereDate('due_on', '<', today('Asia/Karachi'))->whereNotIn('status', ['completed', 'cancelled']);
        }
        if ($kind === 'documents') {
            if (empty($f['archived'])) {
                $q->whereNull('archived_at');
            }
            foreach (['module', 'category'] as $key) {
                if (! empty($f[$key])) {
                    $q->where($key, $f[$key]);
                }
            }
            if (! empty($f['search'])) {
                $q->where('title', 'like', '%'.$f['search'].'%');
            }
            if (! empty($f['reference'])) {
                $q->where('parent_reference', 'like', '%'.$f['reference'].'%');
            }
            if (! empty($f['uploader'])) {
                $q->whereHas('versions', fn ($v) => $v->where('uploaded_by', $f['uploader']));
            }
            foreach (['from', 'to'] as $key) {
                if (! empty($f[$key])) {
                    $q->whereHas('versions', fn ($v) => $v->where('uploaded_at', $key === 'from' ? '>=' : '<', CarbonImmutable::parse($f[$key], 'Asia/Karachi')->startOfDay()->addDays($key === 'to' ? 1 : 0)->utc()));
                }
            }
        }
        if ($kind === 'reports' && ! empty($f['report_id'])) {
            $q->whereKey($f['report_id']);
        }
        if ($kind === 'audit') {
            $q = app(AuditQuery::class)->ordered($q);
        } elseif ($kind === 'tasks') {
            $q->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END")->orderBy('due_on')->orderBy('id');
        } elseif ($kind === 'meetings') {
            $q->orderByDesc('meeting_date')->orderByDesc('id');
        } else {
            $q->orderByDesc('id');
        }
        if ((clone $q)->count() > ($kind === 'audit' ? 10000 : 2500)) {
            throw ValidationException::withMessages(['export' => 'Choose narrower filters; this export exceeds its record limit.']);
        }
        $records = $q->get();
        $headers = $types = [];
        $rows = [];
        switch ($kind) {
            case 'qard':
                $headers = ['Loan', 'Borrower', 'Original fund', 'Approved principal (PKR)', 'Disbursed (PKR)', 'Recovered (PKR)', 'Outstanding (PKR)', 'Status'];
                $types = ['text', 'text', 'text', 'money', 'money', 'money', 'money', 'text'];
                foreach ($records as $r) {
                    $p = app(QardPaymentService::class);
                    $recovered = $p->recoveredMinor($r);
                    $outstanding = $p->outstandingMinor($r);
                    $rows[] = [$r->number, $r->beneficiary?->full_name, $r->fund?->name, $r->principal, Money::decimal(Money::add($recovered, $outstanding)), Money::decimal($recovered), Money::decimal($outstanding), $r->status];
                }break;
            case 'business':
                $headers = ['Case / business', 'Beneficiary', 'Original fund', 'Support', 'Approved (PKR)', 'Released (PKR)', 'Outstanding (PKR)', 'Status'];
                $types = ['text', 'text', 'text', 'text', 'money', 'money', 'money', 'text'];
                foreach ($records as $r) {
                    $rows[] = [$r->number."\n".$r->name, $r->beneficiary?->full_name, $r->fund?->name, $r->support_type, $r->approved_amount, Money::decimal($r->releasedMinor()), Money::decimal($r->outstandingMinor()), $r->status];
                }break;
            case 'projects':
                $headers = ['Project', 'Name', 'Original fund', 'Approved budget (PKR)', 'Net spending (PKR)', 'Remaining (PKR)', 'Progress (%)', 'Status'];
                $types = ['text', 'text', 'text', 'money', 'money', 'money', 'number', 'text'];
                foreach ($records as $r) {
                    $budget = $r->approved()?->budget ?? '0.00';
                    $net = $r->netMinor();
                    $rows[] = [$r->number, $r->name, $r->fund?->name, $budget, Money::decimal($net), Money::decimal($r->remainingMinor()), $r->progress_percent, $r->status];
                }break;
            case 'procurement':
                $headers = ['Purchase', 'Title', 'Supplier', 'Original fund', 'Parent', 'Approved (PKR)', 'Order number', 'Status'];
                $types = ['text', 'text', 'text', 'text', 'text', 'money', 'text', 'text'];
                foreach ($records as $r) {
                    $v = $r->approved() ?? $r->proposal();
                    $rows[] = [$r->number, $v->title, $v->supplier_snapshot['name'] ?? null, $r->fund?->name, $r->parent_type.' / '.$r->parent_id, Money::decimal((int) ($r->approved()?->total_minor ?? 0)), $r->approved()?->issued_at ? 'PO-'.$r->number : null, $r->status];
                }break;
            case 'tasks':
                $headers = ['Task', 'Title', 'Assigned to', 'Committee', 'Project', 'Due', 'Priority', 'Status'];
                $types = ['text', 'text', 'text', 'text', 'text', 'date', 'text', 'text'];
                foreach ($records as $r) {
                    $rows[] = [$r->number, $r->title, $r->assignee?->name, $r->committee?->name, $r->project?->number, $r->due_on, $r->priority, $r->status];
                }break;
            case 'meetings':
                $headers = ['Meeting', 'Title', 'Date', 'Chair', 'Committee', 'Attendees', 'Action items', 'Status'];
                $types = ['text', 'text', 'date', 'text', 'text', 'number', 'number', 'text'];
                foreach ($records as $r) {
                    $rows[] = [$r->number, $r->title, $r->meeting_date, $r->chair?->name, $r->committee?->name, $r->attendees_count, $r->tasks_count, $r->status];
                }break;
            case 'documents':
                $headers = ['Document', 'Title', 'Category', 'Module', 'Source reference', 'Versions', 'Latest upload', 'Status'];
                $types = ['text', 'text', 'text', 'text', 'text', 'number', 'datetime', 'text'];
                foreach ($records as $r) {
                    $rows[] = [$r->number, $r->title, $r->category, $r->module, $r->parent_reference, $r->versions->count(), $r->versions->sortByDesc('version')->first()?->uploaded_at, $r->archived_at ? 'Archived' : 'Active'];
                }break;
            case 'reports':
                $headers = ['Report', 'Title', 'Type', 'Period from', 'Period through', 'Generated', 'Generated by', 'Status'];
                $types = ['text', 'text', 'text', 'date', 'date', 'datetime', 'text', 'text'];
                foreach ($records as $r) {
                    $rows[] = [$r->number, $r->title, $r->type, $r->period_from, $r->period_to, $r->generated_at, $r->generator?->name, 'Retained snapshot'];
                }break;
            case 'audit':
                $headers = ['Event', 'Time (Asia/Karachi)', 'Actor', 'Module', 'Action', 'Outcome', 'Record', 'Summary'];
                $types = array_fill(0, 8, 'text');
                $details = [];
                foreach ($records as $r) {
                    $row = app(AuditQuery::class)->row($r);
                    $rows[] = array_slice(array_values($row), 0, 8);
                    $details[] = [$row['event_uuid'], $row['time'], $row['actor'], $row['reason'], $row['historical'], $row['before'], $row['after'], $row['context']];
                }$sections[] = $this->section('Audit Details', ['Event', 'Time (Asia/Karachi)', 'Actor', 'Recorded reason', 'Historical', 'Before', 'After', 'Context'], array_fill(0, 8, 'text'), $details);
                break;
        }
        array_unshift($sections, $this->section(self::TYPES[$kind][0], $headers, $types, $rows));
        $report = $this->report(self::TYPES[$kind][0], $sections, $actor->name, $f);
        $report['kind'] = $kind;
        $report['snapshot_id'] = $snapshot;
        $report['metrics'][1] = ['label' => $kind === 'audit' ? 'Successful' : 'Approved / active', 'value' => $records->whereIn($kind === 'audit' ? 'outcome' : 'status', $kind === 'audit' ? ['success'] : ['approved', 'active', 'signed', 'in_progress'])->count(), 'type' => 'number'];
        $report['metrics'][2] = ['label' => $kind === 'audit' ? 'Blocked' : 'Completed / repaid', 'value' => $records->whereIn($kind === 'audit' ? 'outcome' : 'status', $kind === 'audit' ? ['blocked'] : ['completed', 'closed', 'repaid', 'held'])->count(), 'type' => 'number'];
        $report['metrics'][3] = ['label' => $kind === 'audit' ? 'Historical' : 'Archived', 'value' => $records->filter(fn ($r) => $kind === 'audit' ? $r->historical : ($r->deleted_at || $r->archived_at))->count(), 'type' => 'number'];
        if ($kind === 'qard' && $actor->hasPermission('business.view')) {
            $report['note'] .= ' This register covers standalone Qard loans; repayable business cases are exported from Small Business.';
        }
        if ($kind === 'documents') {
            $report['note'] .= ' Eligible metadata only; no storage paths, file contents or private download URLs.';
        }

        return $report;
    }

    private function financial(string $kind, array $f, $actor): array
    {
        if ($kind === 'project-report') {
            $rows = app(ProjectReportService::class)->rows($f);
            $columns = ['number' => 'Project', 'name' => 'Name', 'fund' => 'Original fund', 'current_budget' => 'Approved budget (PKR)', 'gross' => 'Gross payments (PKR)', 'refunds' => 'Refunds (PKR)', 'net' => 'Net expenditure (PKR)', 'remaining' => 'Remaining (PKR)', 'original_budget' => 'Original budget (PKR)', 'commitment' => 'Unpaid commitment (PKR)', 'status' => 'Status', 'category' => 'Category', 'responsible' => 'Responsible', 'progress' => 'Progress (%)', 'review_due' => 'Review due', 'completion_overdue' => 'Completion overdue'];
            $money = ['current_budget', 'gross', 'refunds', 'net', 'remaining', 'original_budget', 'commitment'];
        } elseif ($kind === 'procurement-report') {
            $rows = app(ProcurementReportService::class)->rows($actor, $f);
            $columns = ['number' => 'Purchase', 'title' => 'Title', 'supplier' => 'Supplier', 'fund' => 'Original fund', 'approved' => 'Approved (PKR)', 'paid' => 'Paid (PKR)', 'refunds' => 'Refunds (PKR)', 'net' => 'Net spending (PKR)', 'invoiced' => 'Invoiced (PKR)', 'unpaid' => 'Unpaid (PKR)', 'reserved' => 'Reserved (PKR)', 'refund_due' => 'Refund due (PKR)', 'delivery_status' => 'Delivery', 'status' => 'Status', 'parent' => 'Parent', 'accepted_quantity' => 'Accepted quantity', 'returned_quantity' => 'Returned quantity'];
            $money = ['approved', 'paid', 'refunds', 'net', 'invoiced', 'unpaid', 'reserved', 'refund_due'];
        } else {
            $data = app(BusinessReportService::class)->report(array_filter($f, fn ($v) => $v !== null));
            $rows = $data['rows']->map(function ($row) {
                $a = $row['application'];

                return ['number' => $a->number, 'name' => $a->name, 'beneficiary' => $a->beneficiary?->full_name, 'fund' => $a->fund?->name, 'type' => $a->support_type, 'approved' => $a->approved_amount, 'status' => $a->status] + array_map(fn ($v) => Money::decimal($v), array_intersect_key($row, array_flip(['released', 'recovered', 'outstanding', 'period_released', 'period_recovered', 'overdue', 'unused_approval'])));
            });
            $columns = ['number' => 'Business', 'name' => 'Name', 'beneficiary' => 'Beneficiary', 'fund' => 'Original fund', 'type' => 'Support', 'approved' => 'Current approval (PKR)', 'released' => 'Released to end (PKR)', 'recovered' => 'Recovered to end (PKR)', 'outstanding' => 'Outstanding to end (PKR)', 'period_released' => 'Period released (PKR)', 'period_recovered' => 'Period recovered (PKR)', 'overdue' => 'Overdue (PKR)', 'unused_approval' => 'Current unused approval (PKR)', 'status' => 'Current status'];
            $money = ['approved', 'released', 'recovered', 'outstanding', 'period_released', 'period_recovered', 'overdue', 'unused_approval'];
            $f['from'] = $data['from'];
            $f['to'] = $data['to'];
        }
        if ($rows->count() > 2500) {
            throw ValidationException::withMessages(['export' => 'Choose narrower filters; at most 2,500 records are supported.']);
        }
        $sourceKeys = array_values(array_diff(array_keys($columns), ['number']));
        $columns += ['period_from' => 'Period from', 'period_to' => 'Period through', 'generated_on' => 'Generated', 'prepared_by' => 'Prepared by', 'currency' => 'Currency', 'report_type' => 'Report'];
        $rows = $rows->map(fn ($row) => $row + ['period_from' => $f['from'] ?? 'Beginning', 'period_to' => $f['to'] ?? 'Today', 'generated_on' => now('Asia/Karachi')->format('d M Y H:i').' PKT', 'prepared_by' => $actor->name, 'currency' => 'PKR', 'report_type' => self::TYPES[$kind][0]]);
        $sections = [];
        foreach (array_chunk($sourceKeys, 7) as $i => $chunk) {
            $keys = ['number', ...$chunk];
            foreach (['period_from', 'period_to', 'generated_on', 'prepared_by', 'currency', 'report_type'] as $key) {
                if (count($keys) >= 8) {
                    break;
                }if (! in_array($key, $keys)) {
                    $keys[] = $key;
                }
            }
            $sections[] = $this->section('Financial Report '.($i + 1), array_map(fn ($key) => $columns[$key], $keys), array_map(fn ($key) => in_array($key, $money) ? 'money' : (str_ends_with($key, '_quantity') ? 'quantity' : ($key === 'progress' ? 'number' : 'text')), $keys), $rows->map(fn ($row) => array_map(fn ($key) => $row[$key] ?? null, $keys))->all());
        }
        if ($kind === 'business-report') {
            $sections[] = $this->section('Current Shared Fund Capital', ['Fund', 'Purpose', 'Ledger (PKR)', 'Commitments (PKR)', 'Available (PKR)', 'Recovered (PKR)', 'Opening (PKR)', 'Status'], ['text', 'text', 'money', 'money', 'money', 'money', 'money', 'text'], $data['capital']->map(fn ($entry) => [$entry['fund']->name, $entry['fund']->purpose, $entry['summary']['balance'], $entry['summary']['allocated'], $entry['summary']['available'], $entry['summary']['recovered'], $entry['summary']['opening_balance'], $entry['fund']->status])->all());
        }
        $report = $this->report(self::TYPES[$kind][0], $sections, $actor->name, $f);
        $report['snapshot_id'] = null;
        $report['kind'] = $kind;
        $report['note'] = 'Financial movements use the selected period; budgets, status and commitments are current. Posted movements exclude voids. Repeated record references join the sections. All PKR totals are generated by the existing domain reporting service.';
        $metrics = [['label' => 'Records', 'value' => $rows->count(), 'type' => 'number']];
        foreach (array_slice($money, 0, 3) as $key) {
            $metrics[] = ['label' => $columns[$key], 'value' => Money::total($rows->pluck($key)->map(fn ($v) => $v ?? '0.00')), 'type' => 'money'];
        }$report['metrics'] = $metrics;

        return $report;
    }

    public function snapshot(ReportRun $run, string $actor): array
    {
        $rows = [];
        foreach ($run->snapshot['metrics'] as $m) {
            $rows[] = [$run->number, $m['label'], $m['value'], $m['format'] === 'money' ? 'PKR' : 'Count', $run->period_from, $run->period_to, $run->generated_at, $run->generator?->name];
        }
        $sections = [$this->section('Snapshot Metrics', ['Report', 'Metric', 'Value', 'Unit', 'Period from', 'Period through', 'Generated', 'Generated by'], ['text', 'text', 'metric', 'text', 'date', 'date', 'datetime', 'text'], $rows), $this->section('Snapshot Breakdown', ['Report', 'Category / status', 'Count', 'Period from', 'Period through', 'Generated', 'Generated by', 'Source'], ['text', 'text', 'number', 'date', 'date', 'datetime', 'text', 'text'], array_map(fn ($r) => [$run->number, $r['label'], $r['count'], $run->period_from, $run->period_to, $run->generated_at, $run->generator?->name, $run->type], $run->snapshot['rows']))];
        $report = $this->report($run->title, $sections, $actor);
        $report['note'] = 'Retained snapshot '.$run->number.'. '.$run->snapshot['note'];
        $metrics = array_map(fn ($m) => ['label' => $m['label'], 'value' => $m['value'], 'type' => $m['format'] === 'money' ? 'money' : 'number'], array_slice($run->snapshot['metrics'], 0, 4));
        while (count($metrics) < 4) {
            $metrics[] = ['label' => 'Breakdown categories', 'value' => count($run->snapshot['rows']), 'type' => 'number'];
        }$report['metrics'] = $metrics;

        return $report;
    }

    public function section(string $title, array $headers, array $types, array $rows): array
    {
        return ['title' => substr($title, 0, 31), 'headers' => $headers, 'types' => $types, 'widths' => array_fill(0, count($headers), 28), 'rows' => $rows];
    }

    public function report(string $title, array $sections, string $preparedBy, array $filters = []): array
    {
        $count = count($sections[0]['rows']);
        $last = max(16, 15 + $count);

        return ['kind' => 'section', 'title' => $title, 'sections' => $sections, 'count' => $count, 'active' => 0, 'inactive' => 0, 'generated_at' => now('Asia/Karachi'), 'prepared_by' => $preparedBy,
            'template' => config('organization.financial_export_template'), 'scope' => empty(array_filter($filters, fn ($v) => $v !== null && $v !== '')) ? 'All matching records' : 'Selected filters: '.implode(', ', array_keys(array_filter($filters, fn ($v) => $v !== null && $v !== ''))),
            'note' => 'All matching records across all pages. PKR amounts use posted movements only where labelled paid, recovered or spending. Private narratives and evidence are excluded.',
            'metrics' => [['label' => 'Records', 'value' => $count, 'type' => 'number', 'formula' => '=COUNTA(B16:B'.$last.')'], ['label' => 'Active', 'value' => 0, 'type' => 'number'], ['label' => 'Completed', 'value' => 0, 'type' => 'number'], ['label' => 'Archived', 'value' => 0, 'type' => 'number']]];
    }
}
