<?php

namespace AratKruglik\Monobank\DTO;

readonly class Submerchant
{
    public function __construct(
        public string $code,
        public string $iban,
        public ?string $edrpou = null,
        public ?string $owner = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            code: (string) $data['code'],
            iban: (string) $data['iban'],
            edrpou: $data['edrpou'] ?? null,
            owner: $data['owner'] ?? null
        );
    }
}
