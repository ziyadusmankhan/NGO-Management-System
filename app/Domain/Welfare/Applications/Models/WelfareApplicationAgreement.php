<?php

namespace App\Domain\Welfare\Applications\Models;

use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WelfareApplicationAgreement extends Model
{
    protected $fillable = [
        'agreement_number',
        'application_id',
        'approved_amount',
        'agreement_date',
        'effective_date',
        'status',
        'terms',
        'beneficiary_signatory_name',
        'beneficiary_signed_at',
        'organization_signatory_name',
        'organization_signed_at',
        'witness_one_name',
        'witness_two_name',
        'document_path',
        'created_by',
        'prepared_by',
        'prepared_at',
        'fully_signed_at',
        'beneficiary_document_path',
        'organization_document_path',
        'beneficiary_signature_verified_by',
        'organization_signature_verified_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'approved_amount' => 'decimal:2',
            'agreement_date' => 'date',
            'effective_date' => 'date',
            'beneficiary_signed_at' => 'datetime',
            'organization_signed_at' => 'datetime',
            'prepared_at' => 'datetime',
            'fully_signed_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(
            WelfareApplication::class,
            'application_id'
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function preparer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function beneficiarySignatureVerifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'beneficiary_signature_verified_by');
    }

    public function organizationSignatureVerifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organization_signature_verified_by');
    }
}