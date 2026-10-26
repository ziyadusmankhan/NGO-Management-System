<?php

namespace App\Domain\Projects\Models;

use App\Domain\Finance\Support\ReadsDecimalMoney;
use App\Domain\Finance\Transactions\Models\Transaction;
use Illuminate\Database\Eloquent\Model;

class ProjectMovement extends Model
{
    use ReadsDecimalMoney;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['payment_date' => 'date', 'voided_at' => 'datetime'];
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id')->withTrashed();
    }

    public function budgetItem()
    {
        return $this->belongsTo(ProjectBudgetItem::class, 'budget_item_id');
    }

    public function transaction()
    {
        return $this->morphOne(Transaction::class, 'source')->withTrashed();
    }

    public function documents()
    {
        return $this->hasMany(ProjectDocument::class, 'movement_id');
    }

    protected static function booted(): void
    {
        static::updating(function ($movement) {
            $allowed = ['status', 'voided_by', 'voided_at', 'void_reason', 'updated_at'];
            if (array_diff(array_keys($movement->getDirty()), $allowed) || ($movement->isDirty('status') && ! ($movement->getOriginal('status') === 'posted' && $movement->status === 'void'))) {
                throw new \LogicException('Posted project movements are immutable; use an authorized void.');
            }
        });
        static::deleting(fn () => throw new \LogicException('Project financial history cannot be deleted.'));
    }
}
