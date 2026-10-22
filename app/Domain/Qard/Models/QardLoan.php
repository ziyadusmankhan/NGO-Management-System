<?php

namespace App\Domain\Qard\Models;

use Illuminate\Database\Eloquent\Model;

class QardLoan extends Model
{
    protected $guarded = ['id'];
    use \App\Domain\Finance\Support\ReadsDecimalMoney;
    protected array $moneyColumns = ['principal'];
    protected function casts(): array { return ['principal' => 'decimal:2', 'first_due_date' => 'date']; }
    public function beneficiary() { return $this->belongsTo(\App\Domain\Welfare\Beneficiaries\Models\Beneficiary::class)->withTrashed(); }
    public function fund() { return $this->belongsTo(\App\Domain\Finance\Funds\Models\Fund::class)->withTrashed(); }
    public function creator() { return $this->belongsTo(\App\Domain\Users\Models\User::class, 'created_by')->withTrashed(); }
    public function reviews() { return $this->hasMany(QardReview::class); }
    public function agreements() { return $this->hasMany(QardAgreement::class); }
    public function documents() { return $this->hasMany(QardDocument::class); }
    public function movements() { return $this->hasMany(QardMovement::class); }
    public function installments() { return $this->hasMany(QardInstallment::class); }
    public function followups() { return $this->hasMany(QardFollowup::class); }
    public function events() { return $this->hasMany(QardEvent::class); }
    public function reminder() { return $this->hasOne(QardReminder::class); }
}
