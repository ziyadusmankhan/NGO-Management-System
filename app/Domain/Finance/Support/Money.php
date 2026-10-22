<?php

declare(strict_types=1);

namespace App\Domain\Finance\Support;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Illuminate\Database\Eloquent\Builder;

final class Money
{
    public static function minor(mixed $amount): int
    {
        // A union scalar type in a weakly typed caller can coerce 10.25 to int 10.
        // Reject floats explicitly so every caller must supply exact decimal text.
        if (! is_string($amount) && ! is_int($amount)) {
            throw new \InvalidArgumentException('Money must be supplied as decimal text or an integer, never a float.');
        }
        return BigDecimal::of($amount)->toScale(2)->getUnscaledValue()->toInt();
    }

    public static function add(int $left, int $right): int
    {
        return BigInteger::of($left)->plus($right)->toInt();
    }

    public static function decimal(int $minor): string
    {
        return (string) BigDecimal::ofUnscaledValue($minor, 2);
    }

    public static function sum(Builder $query, string $column = 'amount'): string
    {
        return self::decimal(self::sumMinor($query, $column));
    }

    public static function total(iterable $amounts): string
    {
        $total = 0;
        foreach ($amounts as $amount) {
            $total = self::add($total, self::minor($amount));
        }

        return self::decimal($total);
    }

    public static function format(mixed $amount): string
    {
        [$whole, $fraction] = explode('.', self::decimal(self::minor($amount)));

        return preg_replace('/\B(?=(\d{3})+(?!\d))/', ',', $whole).'.'.$fraction;
    }

    public static function sumMinor(Builder $query, string $column = 'amount'): int
    {
        $builder = (clone $query)->toBase();
        // Locking reads see the latest committed ledger even under MySQL REPEATABLE READ.
        if ($builder->getConnection()->transactionLevel() > 0) {
            $builder->lockForUpdate();
        }
        $wrapped = $builder->getGrammar()->wrap($column);
        // SQLite returns DECIMAL as a PHP float; read each fixed-scale value as text
        // before summing in integer minor units. SUM(REAL) is not decimal safe.
        $expression = $builder->getConnection()->getDriverName() === 'sqlite'
            ? "printf('%.2f', {$wrapped})" : $wrapped;
        $total = 0;
        foreach ($builder->select([])->selectRaw($expression.' AS money_value')->cursor() as $row) {
            if ($row->money_value === null) {
                continue;
            }
            $total = self::add($total, self::minor((string) $row->money_value));
        }

        return $total;
    }
}
