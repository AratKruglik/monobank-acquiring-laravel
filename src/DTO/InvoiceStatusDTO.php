<?php

namespace AratKruglik\Monobank\DTO;

use AratKruglik\Monobank\Enums\CurrencyCode;
use AratKruglik\Monobank\Enums\InvoiceStatus;

readonly class InvoiceStatusDTO
{
    public function __construct(
        public string $invoiceId,
        public InvoiceStatus $status,
        public int $amount,
        public ?CurrencyCode $ccy, // Nullable in case of unknown currency
        public ?int $finalAmount = null,
        public ?string $createdDate = null,
        public ?string $modifiedDate = null,
        public ?string $reference = null,
        public ?string $errorCode = null,
        public ?string $errorText = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            invoiceId: (string) $data['invoiceId'],
            status: InvoiceStatus::tryFrom((string) $data['status']) ?? InvoiceStatus::FAILURE, // Fallback or strict? usually strict but safe for DTO
            amount: (int) $data['amount'],
            ccy: CurrencyCode::tryFrom((int) $data['ccy']),
            finalAmount: isset($data['finalAmount']) ? (int) $data['finalAmount'] : null,
            createdDate: $data['createdDate'] ?? null,
            modifiedDate: $data['modifiedDate'] ?? null,
            reference: $data['reference'] ?? null,
            errorCode: $data['errCode'] ?? null,
            errorText: $data['errText'] ?? null
        );
    }
}
