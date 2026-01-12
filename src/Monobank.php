<?php

namespace AratKruglik\Monobank;

use AratKruglik\Monobank\DTO\InvoiceRequestDTO;
use AratKruglik\Monobank\DTO\InvoiceResponseDTO;
use AratKruglik\Monobank\DTO\InvoiceStatusDTO;

use AratKruglik\Monobank\Support\AmountHelper;

use AratKruglik\Monobank\DTO\QrDetailsDTO;

use AratKruglik\Monobank\DTO\SubscriptionRequestDTO;
use AratKruglik\Monobank\DTO\SubscriptionResponseDTO;

class Monobank
{
    protected Client $client;

    public function __construct(protected array $config)
    {
        $this->client = new Client($config);
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
     */
    public function createSubscription(SubscriptionRequestDTO $request): SubscriptionResponseDTO
    {
        $response = $this->client->post('subscription/create', $request->toArray());

        return SubscriptionResponseDTO::fromArray($response->json());
    }

    /**
     * Get subscription details/status.
     * 
     * @return array Raw response data
     */
    public function getSubscriptionDetails(string $subscriptionId): array
    {
        return $this->client->get('subscription/status', ['subscriptionId' => $subscriptionId])->json();
    }

    /**
     * Delete (invalidate) a subscription.
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
     * @param string $invoiceId
     * @param string|null $extRef
     * @param int|float|null $amount Amount to refund (int=cents, float=units)
     * @param array|null $items Items to return (for partial refunds)
     * @return bool
     */
    public function cancelInvoice(string $invoiceId, ?string $extRef = null, int|float|null $amount = null, ?array $items = null): bool
    {
        $data = ['invoiceId' => $invoiceId];
        
        if ($extRef) {
            $data['extRef'] = $extRef;
        }
        if ($amount !== null) {
            $data['amount'] = AmountHelper::toCents($amount);
        }
        if ($items) {
            $data['items'] = $items;
        }

        $response = $this->client->post('invoice/cancel', $data);

        return $response->successful();
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
     * Get list of split receivers (sub-merchants).
     * 
     * @return array
     */
    public function getSplitReceivers(): array
    {
        return $this->client->get('split-receivers')->json();
    }
}