<?php

namespace AratKruglik\Monobank\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \AratKruglik\Monobank\Resources\MerchantResource merchant()
 * @method static string|null getToken()
 * 
 * @see \AratKruglik\Monobank\Monobank
 */
class Monobank extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'monobank';
    }
}
