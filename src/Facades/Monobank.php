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
 * @method static array<\AratKruglik\Monobank\DTO\Submerchant> getSplitReceivers()
 * @method static string|null getToken()
 * @method static bool removeInvoice(string $invoiceId)
 * @method static string finalizeInvoice(string $invoiceId, int|float|null $amount = null, ?array $items = null)
 * @method static \AratKruglik\Monobank\DTO\PaymentResponseDTO paymentDirect(\AratKruglik\Monobank\DTO\PaymentDirectRequestDTO $request)
 * @method static \AratKruglik\Monobank\DTO\InvoiceStatusDTO syncPayment(\AratKruglik\Monobank\DTO\SyncPaymentRequestDTO $request)
 * @method static \AratKruglik\Monobank\DTO\PaymentResponseDTO walletPayment(\AratKruglik\Monobank\DTO\WalletPaymentRequestDTO $request)
 * @method static array<\AratKruglik\Monobank\DTO\WalletItem> getWalletCards(string $walletId)
 * @method static bool deleteWalletCard(string $cardToken)
 * @method static array<\AratKruglik\Monobank\DTO\EmployeeItem> getEmployeeList()
 * @method static array<\AratKruglik\Monobank\DTO\StatementItem> getStatement(int $from, ?int $to = null, ?string $code = null)
 * @method static array<\AratKruglik\Monobank\DTO\FiscalCheck> getFiscalChecks(string $invoiceId)
 * @method static string|null getReceipt(string $invoiceId, ?string $email = null)
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