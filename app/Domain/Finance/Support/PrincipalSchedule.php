<?php

namespace App\Domain\Finance\Support;

use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class PrincipalSchedule
{
    public function make(string $principal, int $count, string $frequency, string $firstDue, ?int $monthlyDay = null): array
    {
        $total = Money::minor($principal);
        if ($count < 1 || $total < $count || ! in_array($frequency, ['weekly', 'monthly'], true)) {
            throw ValidationException::withMessages(['installment_count' => 'Each installment must be at least one paisa and use a weekly or monthly schedule.']);
        }
        $base = intdiv($total, $count);
        $first = CarbonImmutable::parse($firstDue, 'Asia/Karachi');
        $rows = [];
        for ($i = 0; $i < $count; $i++) {
            $due = $frequency === 'weekly' ? $first->addWeeks($i) : $first->addMonthsNoOverflow($i);
            if ($frequency === 'monthly' && $monthlyDay !== null) {
                $due = $due->day(min($monthlyDay, $due->daysInMonth));
            }
            $rows[] = ['sequence' => $i + 1,
                'due_date' => $due->toDateString(),
                'amount' => Money::decimal($i === $count - 1 ? $total - $base * ($count - 1) : $base)];
        }

        return $rows;
    }
}
