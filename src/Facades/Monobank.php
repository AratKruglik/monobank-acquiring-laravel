<?php

namespace AratKruglik\Monobank\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \AratKruglik\Monobank\DTO\InvoiceResponseDTO createInvoice(\AratKruglik\Monobank\DTO\InvoiceRequestDTO $request)
 * @method static \AratKruglik\Monobank\DTO\SubscriptionResponseDTO createSubscription(\AratKruglik\Monobank\DTO\SubscriptionRequestDTO $request)
 * @method static array getSubscriptionDetails(string $subscriptionId)
 * @method static bool deleteSubscription(string $subscriptionId)
 * @method static \AratKruglik\Monobank\DTO\InvoiceStatusDTO getInvoiceStatus(string $invoiceId)
 * @method static bool cancelInvoice(string $invoiceId, ?string $extRef = null, int|float|null $amount = null, ?array $items = null)
 * @method static array getDetails()
 * @method static array getQrList()
 * @method static \AratKruglik\Monobank\DTO\QrDetailsDTO getQrDetails(string $qrId)
 * @method static bool resetQrAmount(string $qrId)
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