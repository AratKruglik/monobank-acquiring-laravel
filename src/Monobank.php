<?php

namespace AratKruglik\Monobank;

use AratKruglik\Monobank\Contracts\ClientInterface;
use AratKruglik\Monobank\DTO\EmployeeItem;
use AratKruglik\Monobank\DTO\FiscalCheck;
use AratKruglik\Monobank\DTO\InvoiceRequestDTO;
use AratKruglik\Monobank\DTO\InvoiceResponseDTO;
use AratKruglik\Monobank\DTO\InvoiceStatusDTO;
use AratKruglik\Monobank\DTO\PaymentDirectRequestDTO;
use AratKruglik\Monobank\DTO\PaymentResponseDTO;
use AratKruglik\Monobank\DTO\QrDetailsDTO;
use AratKruglik\Monobank\DTO\StatementItem;
use AratKruglik\Monobank\DTO\Submerchant;
use AratKruglik\Monobank\DTO\SubscriptionRequestDTO;
use AratKruglik\Monobank\DTO\SubscriptionResponseDTO;
use AratKruglik\Monobank\DTO\SyncPaymentRequestDTO;
use AratKruglik\Monobank\DTO\WalletItem;
use AratKruglik\Monobank\DTO\WalletPaymentRequestDTO;
use AratKruglik\Monobank\Support\AmountHelper;

class Monobank
{
    public function __construct(
        protected array $config,
        protected ClientInterface $client,
    ) {
    }

    /**
     * Get the Monobank Acquiring Token.
     */
    public function getToken(): ?string
    {
        return $this->config['token'] ?? null;
    }

    /**
     * Create a new subscription (recurring payment).
     *
     * Note: this is a documented, officially supported Monobank endpoint (subscription/create),
     * confirmed via the official API docs, though not present in the bundled
     * .claude/skills/monobank-acquiring reference set.
     */
    public function createSubscription(SubscriptionRequestDTO $request): SubscriptionResponseDTO
    {
        $response = $this->client->post('subscription/create', $request->toArray());

        return SubscriptionResponseDTO::fromArray($response->json());
    }

    /**
     * Get subscription details/status.
     *
     * Note: this is a documented, officially supported Monobank endpoint (subscription/status),
     * confirmed via the official API docs, though not present in the bundled
     * .claude/skills/monobank-acquiring reference set.
     *
     * @return array Raw response data
     */
    public function getSubscriptionDetails(string $subscriptionId): array
    {
        return $this->client->get('subscription/status', ['subscriptionId' => $subscriptionId])->json();
    }

    /**
     * Delete (invalidate) a subscription.
     *
     * Note: this is a documented, officially supported Monobank endpoint (subscription/delete),
     * confirmed via the official API docs, though not present in the bundled
     * .claude/skills/monobank-acquiring reference set.
     */
    public function deleteSubscription(string $subscriptionId): bool
    {
        $response = $this->client->post('subscription/delete', ['subscriptionId' => $subscriptionId]);

        return $response->successful();
    }

    /**
     * Create a new invoice.
     */
    public function createInvoice(InvoiceRequestDTO $request): InvoiceResponseDTO
    {
        $response = $this->client->post('invoice/create', $request->toArray());

        return InvoiceResponseDTO::fromArray($response->json());
    }

    /**
     * Get invoice status.
     */
    public function getInvoiceStatus(string $invoiceId): InvoiceStatusDTO
    {
        $response = $this->client->get('invoice/status', ['invoiceId' => $invoiceId]);

        return InvoiceStatusDTO::fromArray($response->json());
    }

    /**
     * Cancel an invoice or process a refund.
     *
     * @param int|float|null $amount Amount to refund (int=cents, float=units)
     * @param array|null $items Items to return (for partial refunds)
     */
    public function cancelInvoice(string $invoiceId, ?string $extRef = null, int|float|null $amount = null, ?array $items = null): bool
    {
        $data = array_filter([
            'invoiceId' => $invoiceId,
            'extRef' => $extRef,
            'amount' => $amount !== null ? AmountHelper::toCents($amount) : null,
            'items' => $items,
        ], fn($value) => $value !== null);

        $response = $this->client->post('invoice/cancel', $data);

        return $response->successful();
    }

    /**
     * Remove (delete) an invoice.
     */
    public function removeInvoice(string $invoiceId): bool
    {
        $response = $this->client->post('invoice/remove', ['invoiceId' => $invoiceId]);

        return $response->successful();
    }

    /**
     * Finalize a "hold" invoice (capture the held amount).
     *
     * @param int|float|null $amount Amount to finalize (int=cents, float=units)
     * @param array|null $items Items to finalize (for partial capture)
     */
    public function finalizeInvoice(string $invoiceId, int|float|null $amount = null, ?array $items = null): string
    {
        $data = array_filter([
            'invoiceId' => $invoiceId,
            'amount' => $amount !== null ? AmountHelper::toCents($amount) : null,
            'items' => $items,
        ], fn($value) => $value !== null);

        $response = $this->client->post('invoice/finalize', $data);

        return $response->json('status');
    }

    /**
     * Process a direct payment using raw card data.
     */
    public function paymentDirect(PaymentDirectRequestDTO $request): PaymentResponseDTO
    {
        $response = $this->client->post('invoice/payment-direct', $request->toArray());

        return PaymentResponseDTO::fromArray($response->json());
    }

    /**
     * Process a synchronous payment using card data, Apple Pay, or Google Pay.
     */
    public function syncPayment(SyncPaymentRequestDTO $request): InvoiceStatusDTO
    {
        $response = $this->client->post('invoice/sync-payment', $request->toArray());

        return InvoiceStatusDTO::fromArray($response->json());
    }

    /**
     * Process a payment using a previously saved wallet card token.
     */
    public function walletPayment(WalletPaymentRequestDTO $request): PaymentResponseDTO
    {
        $response = $this->client->post('wallet/payment', $request->toArray());

        return PaymentResponseDTO::fromArray($response->json());
    }

    /**
     * Get Merchant Details.
     */
    public function getDetails(): array
    {
        return $this->client->get('details')->json();
    }

    /**
     * Get list of QR-cash registers.
     *
     * @return array<QrDetailsDTO>
     */
    public function getQrList(): array
    {
        $response = $this->client->get('qr/list');

        return array_map(
            fn(array $item) => QrDetailsDTO::fromArray($item),
            $response->json()
        );
    }

    /**
     * Get information about a specific QR-cash register.
     */
    public function getQrDetails(string $qrId): QrDetailsDTO
    {
        $response = $this->client->get("qr/details/{$qrId}");

        return QrDetailsDTO::fromArray($response->json());
    }

    /**
     * Reset amount on a QR-cash register.
     */
    public function resetQrAmount(string $qrId): bool
    {
        $response = $this->client->post('qr/reset-amount', ['qrId' => $qrId]);

        return $response->successful();
    }

    /**
     * Get list of wallet cards saved for a customer.
     *
     * @return array<WalletItem>
     */
    public function getWalletCards(string $walletId): array
    {
        $response = $this->client->get('wallet', ['walletId' => $walletId]);

        return array_map(
            fn(array $item) => WalletItem::fromArray($item),
            $response->json('wallet') ?? []
        );
    }

    /**
     * Delete a saved wallet card.
     */
    public function deleteWalletCard(string $cardToken): bool
    {
        $response = $this->client->delete('wallet/card', ['cardToken' => $cardToken]);

        return $response->successful();
    }

    /**
     * Get list of employees.
     *
     * @return array<EmployeeItem>
     */
    public function getEmployeeList(): array
    {
        $response = $this->client->get('employee/list');

        return array_map(
            fn(array $item) => EmployeeItem::fromArray($item),
            $response->json('list') ?? []
        );
    }

    /**
     * Get a statement (transaction report) for a period.
     *
     * @return array<StatementItem>
     */
    public function getStatement(int $from, ?int $to = null, ?string $code = null): array
    {
        $data = array_filter([
            'from' => $from,
            'to' => $to,
            'code' => $code,
        ], fn($value) => $value !== null);

        $response = $this->client->get('statement', $data);

        return array_map(
            fn(array $item) => StatementItem::fromArray($item),
            $response->json('list') ?? []
        );
    }

    /**
     * Get fiscal checks for an invoice.
     *
     * @return array<FiscalCheck>
     */
    public function getFiscalChecks(string $invoiceId): array
    {
        $response = $this->client->get('invoice/fiscal-checks', ['invoiceId' => $invoiceId]);

        return array_map(
            fn(array $item) => FiscalCheck::fromArray($item),
            $response->json('checks') ?? []
        );
    }

    /**
     * Get a receipt file for an invoice.
     */
    public function getReceipt(string $invoiceId, ?string $email = null): ?string
    {
        $data = array_filter([
            'invoiceId' => $invoiceId,
            'email' => $email,
        ], fn($value) => $value !== null);

        $response = $this->client->get('invoice/receipt', $data);

        return $response->json('file');
    }

    /**
     * Get list of split receivers (sub-merchants).
     *
     * @return array<Submerchant>
     */
    public function getSplitReceivers(): array
    {
        $response = $this->client->get('submerchant/list');

        return array_map(
            fn(array $item) => Submerchant::fromArray($item),
            $response->json('list') ?? []
        );
    }
}