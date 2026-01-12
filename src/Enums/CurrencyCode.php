<?php

namespace AratKruglik\Monobank\Enums;

enum CurrencyCode: int
{
    case UAH = 980;
    case USD = 840;
    case EUR = 978;
    case GBP = 826;
    case PLN = 985;
    
    // Helper to try creating from int, falling back or throwing
    public static function fromInt(int $code): ?self
    {
        return self::tryFrom($code);
    }
}
