<?php

namespace AratKruglik\Monobank\DTO;

use AratKruglik\Monobank\Support\AmountHelper;

readonly class CartItemDTO
{
    public int $sum;

    public function __construct(
        public string $name,
        public int $qty,
        int|float $sum,
        public ?string $icon = null,
        public ?string $unit = null,
        public ?string $code = null,
        public ?string $barcode = null,
        public ?string $header = null,
        public ?string $footer = null,
        public ?array $tax = null,
        public ?string $uktzed = null,
        public ?array $discounts = null
    ) {
        $this->sum = AmountHelper::toCents($sum);
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'qty' => $this->qty,
            'sum' => $this->sum,
            'icon' => $this->icon,
            'unit' => $this->unit,
            'code' => $this->code,
            'barcode' => $this->barcode,
            'header' => $this->header,
            'footer' => $this->footer,
            'tax' => $this->tax,
            'uktzed' => $this->uktzed,
            'discounts' => $this->discounts,
        ], fn($value) => !is_null($value));
    }
}
