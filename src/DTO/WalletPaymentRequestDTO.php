<?php

namespace AratKruglik\Monobank\DTO;

use AratKruglik\Monobank\Enums\CurrencyCode;
use AratKruglik\Monobank\Support\AmountHelper;

readonly class WalletPaymentRequestDTO
{
    public int $amount;

    /**
     * Carries a wallet card token; do not log this object or its `toArray()` output.
     *
     * @param int|float $amount Amount in cents (int) or major units (float)
     */
    public function __construct(
        #[\SensitiveParameter] public string $cardToken,
        int|float $amount,
        public string $initiationKind,
        public int|CurrencyCode $ccy = 980,
        public ?string $destination = null,
        public ?string $reference = null,
        public ?string $redirectUrl = null,
        public ?string $webHookUrl = null,
        public ?string $paymentType = null
    ) {
        $this->amount = AmountHelper::toCents($amount);
    }

    public function toArray(): array
    {
        $data = [
            'cardToken' => $this->cardToken,
            'amount' => $this->amount,
            'ccy' => $this->ccy instanceof CurrencyCode ? $this->ccy->value : $this->ccy,
            'initiationKind' => $this->initiationKind,
            'merchantPaymInfo' => array_filter([
                'destination' => $this->destination,
                'reference' => $this->reference,
            ], fn($v) => !is_null($v)),
            'redirectUrl' => $this->redirectUrl,
            'webHookUrl' => $this->webHookUrl,
            'paymentType' => $this->paymentType,
        ];

        return array_filter($data, fn($value) => !is_null($value) && $value !== []);
    }
}
