<?php

namespace AratKruglik\Monobank\DTO;

use AratKruglik\Monobank\Enums\CurrencyCode;
use AratKruglik\Monobank\Support\AmountHelper;

readonly class InvoiceRequestDTO
{
    public int $amount;

    /**
     * @param int|float $amount Amount in cents (int) or major units (float)
     * @param CartItemDTO[]|null $cartItems
     * @param array|null $saveCardData object with agentFeePercent, tipsEmployeeId, displayType
     */
    public function __construct(
        int|float $amount,
        public int|CurrencyCode $ccy = 980,
        public ?string $redirectUrl = null,
        public ?string $successUrl = null,
        public ?string $failUrl = null,
        public ?string $webHookUrl = null,
        public ?int $validity = 3600,
        public ?string $paymentType = 'debit', // debit or hold
        public ?string $qrId = null,
        public ?string $code = null,
        public ?array $saveCardData = null,
        public ?array $cartItems = null,
        public ?string $destination = null,
        public ?string $reference = null
    ) {
        $this->amount = AmountHelper::toCents($amount);
    }

    public function toArray(): array
    {
        $data = [
            'amount' => $this->amount,
            'ccy' => $this->ccy instanceof CurrencyCode ? $this->ccy->value : $this->ccy,
            'merchantPaymInfo' => array_filter([
                'destination' => $this->destination,
                'reference' => $this->reference,
                'basketOrder' => $this->cartItems ? array_map(fn(CartItemDTO $item) => $item->toArray(), $this->cartItems) : null,
            ], fn($v) => !is_null($v)),
            'redirectUrl' => $this->redirectUrl,
            'successUrl' => $this->successUrl,
            'failUrl' => $this->failUrl,
            'webHookUrl' => $this->webHookUrl,
            'validity' => $this->validity,
            'paymentType' => $this->paymentType,
            'qrId' => $this->qrId,
            'code' => $this->code,
            'saveCardData' => $this->saveCardData,
        ];

        return array_filter($data, fn($value) => !is_null($value) && $value !== []);
    }
}
