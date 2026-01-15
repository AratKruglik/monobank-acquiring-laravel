<?php

use AratKruglik\Monobank\Support\AmountHelper;

it('converts int amounts correctly', function () {
    expect(AmountHelper::toCents(100))->toBe(100)
        ->and(AmountHelper::toCents(0))->toBe(0)
        ->and(AmountHelper::toCents(12345))->toBe(12345);
});

it('converts float amounts to cents', function () {
    expect(AmountHelper::toCents(100.0))->toBe(10000)
        ->and(AmountHelper::toCents(1.00))->toBe(100)
        ->and(AmountHelper::toCents(10.50))->toBe(1050)
        ->and(AmountHelper::toCents(0.01))->toBe(1)
        ->and(AmountHelper::toCents(19.99))->toBe(1999);
});

it('throws exception for negative int amounts', function () {
    AmountHelper::toCents(-100);
})->throws(InvalidArgumentException::class, 'Amount cannot be negative');

it('throws exception for negative float amounts', function () {
    AmountHelper::toCents(-10.50);
})->throws(InvalidArgumentException::class, 'Amount cannot be negative');

it('throws exception for NaN', function () {
    AmountHelper::toCents(NAN);
})->throws(InvalidArgumentException::class, 'Amount cannot be NaN or infinite');

it('throws exception for positive infinity', function () {
    AmountHelper::toCents(INF);
})->throws(InvalidArgumentException::class, 'Amount cannot be NaN or infinite');

it('throws exception for negative infinity', function () {
    AmountHelper::toCents(-INF);
})->throws(InvalidArgumentException::class, 'Amount cannot be NaN or infinite');

it('handles zero amounts correctly', function () {
    expect(AmountHelper::toCents(0))->toBe(0)
        ->and(AmountHelper::toCents(0.0))->toBe(0);
});

it('handles very small float amounts', function () {
    expect(AmountHelper::toCents(0.001))->toBe(0)
        ->and(AmountHelper::toCents(0.005))->toBe(1)
        ->and(AmountHelper::toCents(0.009))->toBe(1);
});
