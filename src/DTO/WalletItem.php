<?php

namespace AratKruglik\Monobank\DTO;

readonly class WalletItem
{
    public function __construct(
        public string $cardToken,
        public string $maskedPan,
        public ?string $country = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            cardToken: (string) $data['cardToken'],
            maskedPan: (string) $data['maskedPan'],
            country: $data['country'] ?? null
        );
    }
}
