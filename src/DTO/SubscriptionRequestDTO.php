<?php

namespace AratKruglik\Monobank\DTO;

use AratKruglik\Monobank\Enums\CurrencyCode;
use AratKruglik\Monobank\Support\AmountHelper;

readonly class SubscriptionRequestDTO
{
    public int $amount;

    /**
     * @param int|float $amount Amount in cents (int) or major units (float)
     * @param string $interval Interval format: "{number}{unit}". Examples: "1d", "2w", "1m", "1y".
     * @param string $webHookStatusUrl URL for subscription status changes.
     * @param string|null $webHookChargeUrl URL for successful charges (optional).
     */
    public function __construct(
        int|float $amount,
        public string $interval,
        public string $webHookStatusUrl,
        public ?string $webHookChargeUrl = null,
        public int|CurrencyCode $ccy = 980,
        public ?string $redirectUrl = null,
        public ?int $validity = 86400 // 24 hours
    ) {
        $this->amount = AmountHelper::toCents($amount);
    }

    public function toArray(): array
    {
        $data = [
            'amount' => $this->amount,
            'ccy' => $this->ccy instanceof CurrencyCode ? $this->ccy->value : $this->ccy,
            'redirectUrl' => $this->redirectUrl,
            'webHookUrls' => array_filter([
                'status' => $this->webHookStatusUrl,
                'charge' => $this->webHookChargeUrl,
            ]),
            'interval' => $this->interval,
            'validity' => $this->validity,
        ];

        return array_filter($data, fn($value) => !is_null($value) && $value !== []);
    }
}
