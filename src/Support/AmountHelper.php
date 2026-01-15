<?php

namespace AratKruglik\Monobank\Support;

use InvalidArgumentException;

class AmountHelper
{
    /**
     * Convert an amount to cents (minimal currency units).
     *
     * Rules:
     * - Int is treated as cents (already converted).
     * - Float is treated as major currency unit (e.g. UAH) and converted to cents.
     *
     * Example:
     * - 100 (int) -> 100 cents
     * - 100.0 (float) -> 10000 cents
     * - 100.50 (float) -> 10050 cents
     *
     * @param int|float $amount
     * @return int
     *
     * @throws InvalidArgumentException If amount is negative, NaN, or infinite
     */
    public static function toCents(int|float $amount): int
    {
        if (is_float($amount) && (is_nan($amount) || is_infinite($amount))) {
            throw new InvalidArgumentException('Amount cannot be NaN or infinite');
        }

        if ($amount < 0) {
            throw new InvalidArgumentException('Amount cannot be negative');
        }

        if (is_int($amount)) {
            return $amount;
        }

        // Use round to avoid floating point precision issues (e.g. 19.99 * 100 = 1998.999...)
        return (int) round($amount * 100);
    }
}
