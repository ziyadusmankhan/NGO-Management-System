<?php

namespace App\Domain\Procurement\Models;

use App\Domain\Business\Models\BusinessApplication;
use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Finance\Support\Money;
use App\Domain\Projects\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Procurement extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $attributes = ['revision' => 1, 'version' => 1, 'status' => 'draft', 'frozen' => false];

    protected function casts(): array
    {
        return ['frozen' => 'boolean', 'submitted_at' => 'datetime', 'closed_at' => 'datetime'];
    }

    public function revisions()
    {
        return $this->hasMany(ProcurementRevision::class);
    }

    public function proposal()
    {
        return $this->revisions()->where('revision', $this->revision)->firstOrFail();
    }

    public function approved()
    {
        return $this->approved_revision ? $this->revisions()->where('revision', $this->approved_revision)->firstOrFail() : null;
    }

    public function quotes()
    {
        return $this->hasMany(ProcurementQuote::class);
    }

    public function reviews()
    {
        return $this->hasMany(ProcurementReview::class);
    }

    public function receipts()
    {
        return $this->hasMany(ProcurementReceipt::class);
    }

    public function invoices()
    {
        return $this->hasMany(ProcurementInvoice::class);
    }

    public function lines()
    {
        return $this->hasMany(ProcurementInvoiceLine::class);
    }

    public function returns()
    {
        return $this->hasMany(ProcurementReturn::class);
    }

    public function movements()
    {
        return $this->hasMany(ProcurementMovement::class);
    }

    public function documents()
    {
        return $this->hasMany(ProcurementDocument::class);
    }

    public function events()
    {
        return $this->hasMany(ProcurementEvent::class);
    }

    public function reminder()
    {
        return $this->hasOne(ProcurementReminder::class);
    }

    public function fund()
    {
        return $this->belongsTo(Fund::class)->withTrashed();
    }

    public function parentRecord()
    {
        return match ($this->parent_type) {
            'project' => Project::withTrashed()->findOrFail($this->parent_id), 'business' => BusinessApplication::withTrashed()->findOrFail($this->parent_id), default => null
        };
    }

    public function item(string $key): array
    {
        return collect($this->approved()?->items ?? $this->proposal()->items)->firstWhere('key', $key) ?? [];
    }

    public function paidMinor(?int $line = null): int
    {
        return Money::sumMinor($this->movements()->where('kind', 'payment')->where('status', 'posted')->when($line, fn ($q) => $q->where('invoice_line_id', $line))->getQuery());
    }

    public function refundedMinor(?int $line = null): int
    {
        return Money::sumMinor($this->movements()->where('kind', 'refund')->where('status', 'posted')->when($line, fn ($q) => $q->where('invoice_line_id', $line))->getQuery());
    }

    public function invoiceBalance(ProcurementInvoiceLine $line): int
    {
        return max(0, $line->amount_minor - $this->returns()->where('invoice_line_id', $line->id)->where('status', 'posted')->sum('credit_minor') - $this->paidMinor($line->id) + $this->refundedMinor($line->id));
    }

    public function reservation(?string $key = null): int
    {
        if (! $this->approved_revision || in_array($this->status, ['closed', 'cancelled'])) {
            return 0;
        }
        $total = 0;
        foreach ($this->approved()->items as $i) {
            if ($key && $i['key'] !== $key) {
                continue;
            }
            $lines = $this->lines()->where('item_key', $i['key'])->pluck('id');
            $paid = Money::sumMinor($this->movements()->whereIn('invoice_line_id', $lines)->where('kind', 'payment')->where('status', 'posted')->getQuery());
            $refund = Money::sumMinor($this->movements()->whereIn('invoice_line_id', $lines)->where('kind', 'refund')->where('status', 'posted')->getQuery());
            $credit = (int) $this->returns()->where('item_key', $i['key'])->where('status', 'posted')->sum('credit_minor');
            $total = Money::add($total, max(0, $i['amount_minor'] - $credit - $paid + $refund));
        }

        return $total;
    }
}
