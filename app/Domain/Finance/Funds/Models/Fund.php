<?php

namespace App\Domain\Finance\Funds\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Domain\Finance\Transactions\Models\Transaction;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Domain\Finance\Donations\Models\Donation;
use App\Domain\Finance\Expenses\Models\Expense;

class Fund extends Model
{
    use HasFactory, SoftDeletes, \App\Domain\Finance\Support\ReadsDecimalMoney;

    protected array $moneyColumns = ['opening_balance'];

    protected $attributes = ['opening_balance' => '0.00'];

    public const CONTROL_FLAGS = [
        'restrict_spending', 'require_approval', 'require_documents', 'monitor_balance',
        'record_transactions', 'prevent_negative_balance', 'maintain_audit_history', 'protect_restricted_designation',
    ];

    public function changes(): HasMany
    {
        return $this->hasMany(FundChange::class)->orderByDesc('id');
    }

    

    protected $fillable = [
        'fund_reference',
        'name',
        'code',
        'type',
        'purpose',
        'description',
        'usage_rules',
        'responsible_authority',
        'opening_balance',
        'opening_balance_date',
        'status',
        'fund_id',
        'restrict_spending',
        'require_approval',
        'require_documents',
        'monitor_balance',
        'record_transactions',
        'prevent_negative_balance',
        'maintain_audit_history',
        'protect_restricted_designation',
        'internal_notes',
    ];
    public function transactions(): HasMany
    
    {
        return $this->hasMany(Transaction::class, 'fund_id');
        
    }
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'fund_id');
    }
    public function donations(): HasMany
    {
        return $this->hasMany(
            Donation::class,
            'fund_id'
        );
    }

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'opening_balance_date' => 'date',

        'restrict_spending' => 'boolean',
        'require_approval' => 'boolean',
        'require_documents' => 'boolean',
        'monitor_balance' => 'boolean',
        'record_transactions' => 'boolean',
        'prevent_negative_balance' => 'boolean',
        'maintain_audit_history' => 'boolean',
        'protect_restricted_designation' => 'boolean',
    ];
}
