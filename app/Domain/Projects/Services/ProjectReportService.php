<?php

namespace App\Domain\Projects\Services;

use App\Domain\Finance\Support\Money;
use App\Domain\Projects\Models\Project;

class ProjectReportService
{
    public function rows(array $filters)
    {
        return Project::withTrashed()->with(['fund', 'reminder'])->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['fund_id'] ?? null, fn ($q, $v) => $q->where('fund_id', $v))->orderBy('id')->get()
            ->filter(function ($p) use ($filters) {
                $r = $p->approved() ?? $p->proposal();

                return (! ($filters['category_id'] ?? null) || $r->category_id == $filters['category_id']) && (! ($filters['responsible_id'] ?? null) || $r->responsible_id == $filters['responsible_id']);
            })
            ->map(function ($p) use ($filters) {
                $r = $p->approved() ?? $p->proposal();
                $original = $p->revisions()->whereNotNull('approved_at')->orderBy('revision')->first();
                $q = $p->movements()->where('status', 'posted')->when($filters['from'] ?? null, fn ($q, $v) => $q->whereDate('payment_date', '>=', $v))->when($filters['to'] ?? null, fn ($q, $v) => $q->whereDate('payment_date', '<=', $v));
                $paid = Money::sumMinor((clone $q)->where('kind', 'payment')->getQuery());
                $refunds = Money::sumMinor((clone $q)->where('kind', 'refund')->getQuery());

                return ['project' => $p, 'number' => $p->number, 'name' => $p->name, 'status' => $p->status, 'category' => $r->category_name, 'responsible' => $r->responsible?->name, 'fund' => $p->fund?->name ?? 'Unassigned',
                    'original_budget' => $original?->budget ?? '0.00', 'current_budget' => $p->approved()?->budget ?? '0.00', 'gross' => Money::decimal($paid), 'refunds' => Money::decimal($refunds), 'net' => Money::decimal($paid - $refunds),
                    'remaining' => Money::decimal($p->remainingMinor()), 'commitment' => Money::decimal($p->commitmentMinor()), 'progress' => $p->progress_percent, 'review_due' => $p->reminder?->review_due ? 'Yes' : 'No', 'completion_overdue' => $p->reminder?->completion_overdue ? 'Yes' : 'No'];
            })->values();
    }

    public function totals($rows): array
    {
        $totals = array_fill_keys(['original_budget', 'current_budget', 'gross', 'refunds', 'net', 'remaining', 'commitment'], 0);
        foreach ($rows as $row) {
            foreach ($totals as $key => $v) {
                $totals[$key] = Money::add($v, Money::minor($row[$key]));
            }
        }

        return array_map(fn ($v) => Money::decimal($v), $totals);
    }
}
