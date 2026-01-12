<?php

namespace AratKruglik\Monobank\DTO;

readonly class InvoiceResponseDTO
{
    public function __construct(
        public string $invoiceId,
        public string $pageUrl
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            invoiceId: (string) $data['invoiceId'],
            pageUrl: (string) $data['pageUrl']
        );
    }
}
