<?php

namespace AratKruglik\Monobank\DTO;

use AratKruglik\Monobank\Enums\CurrencyCode;
use AratKruglik\Monobank\Enums\InvoiceStatus;

readonly class PaymentResponseDTO
{
    public function __construct(
        public string $invoiceId,
        public InvoiceStatus $status,
        public int $amount,
        public ?CurrencyCode $ccy,
        public ?string $createdDate = null,
        public ?string $modifiedDate = null,
        public ?string $failureReason = null,
        public ?string $tdsUrl = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            invoiceId: (string) $data['invoiceId'],
            status: InvoiceStatus::tryFrom((string) $data['status']) ?? InvoiceStatus::FAILURE,
            amount: (int) $data['amount'],
            ccy: isset($data['ccy']) ? CurrencyCode::tryFrom((int) $data['ccy']) : null,
            createdDate: $data['createdDate'] ?? null,
            modifiedDate: $data['modifiedDate'] ?? null,
            failureReason: $data['failureReason'] ?? null,
            tdsUrl: $data['tdsUrl'] ?? null
        );
    }
}
