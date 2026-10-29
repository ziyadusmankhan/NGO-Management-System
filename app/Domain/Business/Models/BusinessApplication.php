<?php

namespace App\Domain\Business\Models;

use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Finance\Support\Money;
use App\Domain\Finance\Support\ReadsDecimalMoney;
use App\Domain\Security\Concerns\EncryptsSensitive;
use App\Domain\Users\Models\User;
use App\Domain\Welfare\Beneficiaries\Models\Beneficiary;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessApplication extends Model
{
    use EncryptsSensitive;
    use ReadsDecimalMoney, SoftDeletes;

    protected $guarded = ['id'];

    protected array $moneyColumns = ['total_investment', 'applicant_contribution', 'approved_amount', 'expected_sales', 'expected_expenses'];

    protected function casts(): array
    {
        return ['beneficiary_snapshot' => 'array', 'expected_start_date' => 'date', 'started_on' => 'date', 'next_review_date' => 'date', 'funding_closed_at' => 'datetime', 'closed_at' => 'datetime'];
    }

    public function beneficiary()
    {
        return $this->belongsTo(Beneficiary::class)->withTrashed();
    }

    public function fund()
    {
        return $this->belongsTo(Fund::class)->withTrashed();
    }

    public function category()
    {
        return $this->belongsTo(BusinessCategory::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function budgetItems()
    {
        return $this->hasMany(BusinessBudgetItem::class);
    }

    public function currentBudget()
    {
        return $this->budgetItems()->where('revision', $this->revision);
    }

    public function reviews()
    {
        return $this->hasMany(BusinessReview::class);
    }

    public function agreements()
    {
        return $this->hasMany(BusinessAgreement::class);
    }

    public function documents()
    {
        return $this->hasMany(BusinessDocument::class);
    }

    public function movements()
    {
        return $this->hasMany(BusinessMovement::class);
    }

    public function installments()
    {
        return $this->hasMany(BusinessInstallment::class);
    }

    public function monitorings()
    {
        return $this->hasMany(BusinessMonitoring::class);
    }

    public function handovers()
    {
        return $this->hasMany(BusinessHandover::class);
    }

    public function events()
    {
        return $this->hasMany(BusinessEvent::class);
    }

    public function reminder()
    {
        return $this->hasOne(BusinessReminder::class);
    }

    public function releasedMinor(): int
    {
        return Money::sumMinor($this->movements()->where('kind', 'disbursement')->where('status', 'posted')->getQuery());
    }

    public function recoveredMinor(): int
    {
        return Money::sumMinor($this->movements()->where('kind', 'repayment')->where('status', 'posted')->getQuery());
    }

    public function outstandingMinor(): int
    {
        return $this->support_type === 'repayable' ? $this->releasedMinor() - $this->recoveredMinor() : 0;
    }

    public function remainingMinor(): int
    {
        return $this->funding_status === 'finalized' || in_array($this->status, ['closed', 'cancelled']) ? 0 : max(0, Money::minor($this->approved_amount) - $this->releasedMinor());
    }

    public function recoveryStatus(): string
    {
        return $this->support_type === 'grant' ? 'Not applicable' : ($this->releasedMinor() === 0 ? 'Not funded' : ($this->outstandingMinor() === 0 ? 'Repaid' : ($this->funding_status === 'open' ? 'Funding in progress' : 'Repaying')));
    }
}
