<?php

namespace AratKruglik\Monobank\DTO;

use AratKruglik\Monobank\Enums\CurrencyCode;
use AratKruglik\Monobank\Support\AmountHelper;

/**
 * Contains sensitive cardholder data; do not log this object or its `toArray()` output.
 */
readonly class PaymentDirectRequestDTO
{
    public int $amount;

    /**
     * @param int|float $amount Amount in cents (int) or major units (float)
     * @param array $cardData array{pan:string, exp:string, cvv:string}
     * @param array|null $saveCardData object with agentFeePercent, tipsEmployeeId, displayType
     */
    public function __construct(
        int|float $amount,
        #[\SensitiveParameter] public array $cardData,
        public int|CurrencyCode $ccy = 980,
        public ?string $destination = null,
        public ?string $reference = null,
        public ?string $redirectUrl = null,
        public ?string $webHookUrl = null,
        public ?string $paymentType = 'debit',
        public ?array $saveCardData = null,
        public ?string $initiationKind = null
    ) {
        $this->amount = AmountHelper::toCents($amount);
    }

    public function toArray(): array
    {
        $data = [
            'amount' => $this->amount,
            'ccy' => $this->ccy instanceof CurrencyCode ? $this->ccy->value : $this->ccy,
            'cardData' => $this->cardData,
            'merchantPaymInfo' => array_filter([
                'destination' => $this->destination,
                'reference' => $this->reference,
            ], fn($v) => !is_null($v)),
            'redirectUrl' => $this->redirectUrl,
            'webHookUrl' => $this->webHookUrl,
            'paymentType' => $this->paymentType,
            'saveCardData' => $this->saveCardData,
            'initiationKind' => $this->initiationKind,
        ];

        return array_filter($data, fn($value) => !is_null($value) && $value !== []);
    }
}
