<?php

namespace AratKruglik\Monobank\DTO;

readonly class EmployeeItem
{
    public function __construct(
        public string $id,
        public string $name,
        public string $extRef
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) $data['id'],
            name: (string) $data['name'],
            extRef: (string) $data['extRef']
        );
    }
}
