<?php

namespace AratKruglik\Monobank\DTO;

use AratKruglik\Monobank\Enums\CurrencyCode;

readonly class StatementItem
{
    public function __construct(
        public string $invoiceId,
        public string $status,
        public string $maskedPan,
        public string $date,
        public int $amount,
        public ?CurrencyCode $ccy,
        public ?int $profitAmount,
        public ?string $reference,
        public ?string $destination,
        public ?string $approvalCode,
        public ?string $rrn,
        public string $paymentScheme,
        public ?string $shortQrId = null,
        public array $cancelList = []
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            invoiceId: (string) $data['invoiceId'],
            status: (string) $data['status'],
            maskedPan: (string) $data['maskedPan'],
            date: (string) $data['date'],
            amount: (int) $data['amount'],
            ccy: isset($data['ccy']) ? CurrencyCode::tryFrom((int) $data['ccy']) : null,
            profitAmount: isset($data['profitAmount']) ? (int) $data['profitAmount'] : null,
            reference: $data['reference'] ?? null,
            destination: $data['destination'] ?? null,
            approvalCode: $data['approvalCode'] ?? null,
            rrn: $data['rrn'] ?? null,
            paymentScheme: (string) ($data['paymentScheme'] ?? ''),
            shortQrId: $data['shortQrId'] ?? null,
            cancelList: $data['cancelList'] ?? []
        );
    }
}
