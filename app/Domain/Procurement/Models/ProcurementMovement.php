<?php

namespace App\Domain\Procurement\Models;

use App\Domain\Business\Models\BusinessMovement;
use App\Domain\Finance\Support\ReadsDecimalMoney;
use App\Domain\Finance\Transactions\Models\Transaction;
use App\Domain\Projects\Models\ProjectMovement;
use Illuminate\Database\Eloquent\Model;

class ProcurementMovement extends Model
{
    use ReadsDecimalMoney;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['payment_date' => 'date', 'voided_at' => 'datetime'];
    }

    public function procurement()
    {
        return $this->belongsTo(Procurement::class)->withTrashed();
    }

    public function line()
    {
        return $this->belongsTo(ProcurementInvoiceLine::class, 'invoice_line_id');
    }

    public function transaction()
    {
        return $this->morphOne(Transaction::class, 'source')->withTrashed();
    }

    public function sourceMovement()
    {
        return match ($this->source_type) {
            'project' => ProjectMovement::findOrFail($this->source_id),'business' => BusinessMovement::findOrFail($this->source_id),default => $this
        };
    }

    public function ledger()
    {
        return $this->sourceMovement()->transaction;
    }

    protected static function booted(): void
    {
        static::updating(function ($m) {
            $allowed = ['status', 'voided_by', 'voided_at', 'void_reason', 'updated_at'];
            if (! $m->getOriginal('source_id')) {
                $allowed = [...$allowed, 'source_id', 'source_type'];
            }
            if (array_diff(array_keys($m->getDirty()), $allowed) || ($m->isDirty('status') && ! ($m->getOriginal('status') === 'posted' && $m->status === 'void'))) {
                throw new \LogicException('Procurement payments are immutable. Use a recorded void.');
            }
        });
        static::deleting(fn () => throw new \LogicException('Retain procurement financial history.'));
    }
}
