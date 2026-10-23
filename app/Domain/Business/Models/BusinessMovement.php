<?php

namespace App\Domain\Business\Models;

use App\Domain\Finance\Support\ReadsDecimalMoney;
use App\Domain\Finance\Transactions\Models\Transaction;
use Illuminate\Database\Eloquent\Model;

class BusinessMovement extends Model
{
    use ReadsDecimalMoney;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['payment_date' => 'date', 'voided_at' => 'datetime'];
    }

    public function application()
    {
        return $this->belongsTo(BusinessApplication::class, 'business_application_id')->withTrashed();
    }

    public function budgetItem()
    {
        return $this->belongsTo(BusinessBudgetItem::class, 'budget_item_id');
    }

    public function transaction()
    {
        return $this->morphOne(Transaction::class, 'source')->withTrashed();
    }

    public function allocations()
    {
        return $this->hasMany(BusinessAllocation::class);
    }

    public function documents()
    {
        return $this->hasMany(BusinessDocument::class, 'movement_id');
    }

    public function handover()
    {
        return $this->hasOne(BusinessHandover::class, 'movement_id');
    }

    protected static function booted(): void
    {
        static::updating(function ($movement) {
            $allowed = ['status', 'voided_by', 'voided_at', 'void_reason', 'updated_at'];
            if (array_diff(array_keys($movement->getDirty()), $allowed) || ($movement->isDirty('status') && ! ($movement->getOriginal('status') === 'posted' && $movement->status === 'void'))) {
                throw new \LogicException('Posted business movements are immutable; use an authorized void.');
            }
        });
        static::deleting(fn () => throw new \LogicException('Business financial history cannot be deleted.'));
    }
}
