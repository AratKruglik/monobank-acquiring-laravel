<?php

namespace AratKruglik\Monobank\DTO;

readonly class FiscalCheck
{
    public function __construct(
        public string $id,
        public string $status,
        public string $type,
        public string $fiscalizationSource,
        public ?string $statusDescription = null,
        public ?string $taxUrl = null,
        public ?string $file = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) $data['id'],
            status: (string) $data['status'],
            type: (string) $data['type'],
            fiscalizationSource: (string) $data['fiscalizationSource'],
            statusDescription: $data['statusDescription'] ?? null,
            taxUrl: $data['taxUrl'] ?? null,
            file: $data['file'] ?? null
        );
    }
}
