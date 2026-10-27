<?php

namespace App\Domain\Audit\Services;

use App\Domain\Audit\Models\AuditEvent;
use App\Domain\Welfare\Assistance\Models\WelfareAssistancePayment;
use Illuminate\Support\Facades\DB;

class AuditHistoryImporter
{
    public function run(bool $dry = false): array
    {
        $stats = ['source_count' => 0, 'imported_count' => 0, 'existing_count' => 0, 'excluded_count' => 0];
        foreach (config('audit.models') as $class => $config) {
            if (! ($config['history'] ?? false)) {
                continue;
            }
            $class::query()->chunkById(200, function ($rows) use (&$stats, $dry) {
                foreach ($rows as $m) {
                    $stats['source_count']++;
                    $key = $m->getTable().':'.$m->id;
                    if (AuditEvent::where('source_key', $key)->exists()) {
                        $stats['existing_count']++;

                        continue;
                    }
                    if (app(AuditCapture::class)->representedByProcurement($m)) {
                        $stats['excluded_count']++;

                        continue;
                    }
                    if ($dry) {
                        $stats['imported_count']++;

                        continue;
                    }
                    $event = DB::transaction(fn () => app(AuditCapture::class)->history($m, true), 5);
                    $stats[$event ? 'imported_count' : 'excluded_count']++;
                }
            });
        }
        WelfareAssistancePayment::query()->chunkById(200, function ($rows) use (&$stats, $dry) {
            foreach ($rows as $m) {
                foreach (['posted', 'void'] as $status) {
                    if ($status === 'void' && $m->status !== 'void') {
                        continue;
                    }
                    $stats['source_count']++;
                    $key = 'welfare_payments:'.$m->id.':'.$status;
                    if (AuditEvent::where('source_key', $key)->exists()) {
                        $stats['existing_count']++;

                        continue;
                    }
                    if (! $dry) {
                        DB::transaction(function () use ($m, $status, $key) {
                            $t = $m->transaction()->withTrashed()->first();
                            app(AuditRecorder::class)->record('welfare', $status === 'void' ? 'payment_voided' : 'payment_posted', [
                                'source_key' => $key, 'historical' => true, 'actor_id' => $status === 'void' ? $m->voided_by : $m->recorded_by,
                                'occurred_at' => $status === 'void' ? $m->voided_at : $m->created_at, 'subject_type' => get_class($m), 'subject_id' => $m->id, 'subject_reference' => $m->payment_number,
                                'after' => $m->only(['amount', 'fund_id', 'payment_date', 'payment_method']) + ['status' => $status],
                                'reason' => $status === 'void' ? $m->void_reason : null, 'context' => ['ledger_entries' => $t ? [$t->only(['id', 'transaction_number', 'fund_id', 'amount', 'direction', 'source_type', 'source_id'])] : []],
                            ]);
                        }, 5);
                    }
                    $stats['imported_count']++;
                }
            }
        });
        if (! $dry) {
            app(AuditRecorder::class)->record('audit', 'history_import_completed', ['actor_id' => null, 'actor_kind' => 'system', 'after' => $stats]);
        }

        return $stats;
    }
}
