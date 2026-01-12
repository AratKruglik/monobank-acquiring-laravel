<?php

namespace AratKruglik\Monobank\Resources;

use AratKruglik\Monobank\Client;
use AratKruglik\Monobank\DTO\InvoiceRequestDTO;
use AratKruglik\Monobank\DTO\InvoiceResponseDTO;
use AratKruglik\Monobank\DTO\InvoiceStatusDTO;

class MerchantResource
{
    public function __construct(protected Client $client)
    {
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
     * @param int|null $amount Amount to refund (for partial refunds)
     * @param array|null $items Items to return (for partial refunds)
     * @return bool
     */
    public function cancelInvoice(string $invoiceId, ?string $extRef = null, ?int $amount = null, ?array $items = null): bool
    {
        $data = ['invoiceId' => $invoiceId];
        
        if ($extRef) {
            $data['extRef'] = $extRef;
        }
        if ($amount) {
            $data['amount'] = $amount;
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
}
