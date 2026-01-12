<?php

namespace AratKruglik\Monobank\DTO;

readonly class QrDetailsDTO
{
    public function __construct(
        public string $qrId,
        public string $shortQrId,
        public string $paymentType,
        public string $pageUrl,
        public ?string $amount = null, // Can be null if no amount set
        public ?int $ccy = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            qrId: (string) $data['qrId'],
            shortQrId: (string) $data['shortQrId'],
            paymentType: (string) $data['paymentType'],
            pageUrl: (string) $data['pageUrl'],
            amount: isset($data['amount']) ? (string) $data['amount'] : null,
            ccy: isset($data['ccy']) ? (int) $data['ccy'] : null
        );
    }
}
