<?php

namespace App\Domain\Projects\Models;

use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Finance\Support\Money;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $attributes = ['revision' => 1, 'version' => 1, 'status' => 'draft', 'payments_frozen' => false, 'progress_percent' => 0];

    protected function casts(): array
    {
        return ['payments_frozen' => 'boolean', 'fund_locked_at' => 'datetime', 'started_on' => 'date', 'completed_on' => 'date', 'closed_at' => 'datetime', 'next_review_date' => 'date'];
    }

    public function revisions()
    {
        return $this->hasMany(ProjectRevision::class);
    }

    public function proposal(): ProjectRevision
    {
        return $this->revisions()->where('revision', $this->revision)->firstOrFail();
    }

    public function approved(): ?ProjectRevision
    {
        return $this->approved_revision ? $this->revisions()->where('revision', $this->approved_revision)->firstOrFail() : null;
    }

    public function budgets(?int $revision = null)
    {
        return $this->hasMany(ProjectBudgetVersion::class)->where('revision', $revision ?? $this->revision);
    }

    public function items()
    {
        return $this->hasMany(ProjectBudgetItem::class);
    }

    public function reviews()
    {
        return $this->hasMany(ProjectReview::class);
    }

    public function movements()
    {
        return $this->hasMany(ProjectMovement::class);
    }

    public function documents()
    {
        return $this->hasMany(ProjectDocument::class);
    }

    public function progress()
    {
        return $this->hasMany(ProjectProgress::class);
    }

    public function events()
    {
        return $this->hasMany(ProjectEvent::class);
    }

    public function reminder()
    {
        return $this->hasOne(ProjectReminder::class);
    }

    public function fund()
    {
        return $this->belongsTo(Fund::class)->withTrashed();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function grossMinor(?int $item = null): int
    {
        return Money::sumMinor($this->movements()->where('kind', 'payment')->where('status', 'posted')->when($item, fn ($q) => $q->where('budget_item_id', $item))->getQuery());
    }

    public function refundsMinor(?int $item = null): int
    {
        return Money::sumMinor($this->movements()->where('kind', 'refund')->where('status', 'posted')->when($item, fn ($q) => $q->where('budget_item_id', $item))->getQuery());
    }

    public function netMinor(?int $item = null): int
    {
        return $this->grossMinor($item) - $this->refundsMinor($item);
    }

    public function remainingMinor(): int
    {
        return max(0, Money::minor($this->approved()?->budget ?? '0') - $this->netMinor());
    }

    public function commitmentMinor(): int
    {
        return in_array($this->status, ['approved', 'in_progress', 'completed']) ? $this->remainingMinor() : 0;
    }
}
