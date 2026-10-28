<?php

namespace App\Domain\Welfare\Applications\Models;

use App\Domain\Audit\Concerns\RecordsAudit;
use App\Domain\Finance\Support\ReadsDecimalMoney;
use App\Domain\Security\Concerns\EncryptsSensitive;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WelfareApplicationReview extends Model
{
    use EncryptsSensitive;
    use ReadsDecimalMoney;
    use RecordsAudit;

    protected array $moneyColumns = ['recommended_amount'];

    use HasFactory;

    protected $fillable = [
        'review_number',
        'application_id',
        'stage',
        'decision',
        'recommended_amount',
        'notes',
        'decided_by',
        'decided_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'recommended_amount' => 'decimal:2',
            'decided_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * Welfare application being reviewed.
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(
            WelfareApplication::class,
            'application_id'
        );
    }

    /**
     * User who made the decision.
     */
    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'decided_by'
        );
    }
}
