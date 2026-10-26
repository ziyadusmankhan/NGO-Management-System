<?php

namespace App\Domain\Procurement\Services;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;
use Illuminate\Validation\ValidationException;

final class ProcurementMath
{
    public static function quantity(string $value): int
    {
        return BigDecimal::of($value)->toScale(3)->getUnscaledValue()->toInt();
    }

    public static function display(int $value): string
    {
        return (string) BigDecimal::ofUnscaledValue($value, 3);
    }

    public static function cost(int $quantity, int $unitMinor): int
    {
        $amount = BigInteger::of($quantity)->multipliedBy($unitMinor)->toBigDecimal()->dividedBy(1000, 0, RoundingMode::HalfUp);
        if ($amount->isGreaterThan('999999999999999')) {
            throw ValidationException::withMessages(['items' => 'The item total exceeds the supported amount. Reduce its quantity or unit price.']);
        }

        return $amount->toInt();
    }

    public static function portion(int $total, int $quantity, int $whole): int
    {
        return BigInteger::of($total)->multipliedBy($quantity)->toBigDecimal()->dividedBy($whole, 0, RoundingMode::HalfUp)->toInt();
    }
}
