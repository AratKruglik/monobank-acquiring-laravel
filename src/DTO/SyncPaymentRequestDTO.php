<?php

namespace AratKruglik\Monobank\DTO;

use AratKruglik\Monobank\Enums\CurrencyCode;
use AratKruglik\Monobank\Support\AmountHelper;
use InvalidArgumentException;

/**
 * Contains sensitive cardholder data; do not log this object or its `toArray()` output.
 */
readonly class SyncPaymentRequestDTO
{
    public int $amount;

    /**
     * @param int|float $amount Amount in cents (int) or major units (float)
     * @param array|null $cardData array{pan:string, exp:string, cvv:string}
     */
    public function __construct(
        int|float $amount,
        public int|CurrencyCode $ccy = 980,
        #[\SensitiveParameter] public ?array $cardData = null,
        #[\SensitiveParameter] public ?array $applePay = null,
        #[\SensitiveParameter] public ?array $googlePay = null,
        public ?string $destination = null,
        public ?string $reference = null
    ) {
        $this->amount = AmountHelper::toCents($amount);

        $sources = array_filter([$this->cardData, $this->applePay, $this->googlePay], fn($v) => $v !== null);

        if (count($sources) !== 1) {
            throw new InvalidArgumentException('Exactly one of cardData, applePay, or googlePay must be provided.');
        }
    }

    public function toArray(): array
    {
        $data = [
            'amount' => $this->amount,
            'ccy' => $this->ccy instanceof CurrencyCode ? $this->ccy->value : $this->ccy,
            'cardData' => $this->cardData,
            'applePay' => $this->applePay,
            'googlePay' => $this->googlePay,
            'merchantPaymInfo' => array_filter([
                'destination' => $this->destination,
                'reference' => $this->reference,
            ], fn($v) => !is_null($v)),
        ];

        return array_filter($data, fn($value) => !is_null($value) && $value !== []);
    }
}
