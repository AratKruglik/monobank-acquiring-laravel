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
