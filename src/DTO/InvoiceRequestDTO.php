<?php

namespace AratKruglik\Monobank\DTO;

use AratKruglik\Monobank\Enums\CurrencyCode;
use AratKruglik\Monobank\Support\AmountHelper;

readonly class InvoiceRequestDTO
{
    public int $amount;

    /**
     * @param int|float $amount Amount in cents (int) or major units (float, e.g. 10.50)
     * @param CartItemDTO[]|null $cartItems
     */
    public function __construct(
        int|float $amount,
        public int|CurrencyCode $ccy = 980, // UAH by default
        public ?array $merchantPaymInfo = null,
        public ?string $redirectUrl = null,
        public ?string $webHookUrl = null,
        public ?int $validity = 3600,
        public ?string $paymentType = 'debit',
        public ?string $qrId = null,
        public ?string $code = null,
        public ?array $cartItems = null, // Array of CartItemDTO
        public bool $saveCardData = false
    ) {
        $this->amount = AmountHelper::toCents($amount);
    }

    public function toArray(): array
    {
        $data = [
            'amount' => $this->amount,
            'ccy' => $this->ccy instanceof CurrencyCode ? $this->ccy->value : $this->ccy,
            'merchantPaymInfo' => $this->merchantPaymInfo,
            'redirectUrl' => $this->redirectUrl,
            'webHookUrl' => $this->webHookUrl,
            'validity' => $this->validity,
            'paymentType' => $this->paymentType,
            'qrId' => $this->qrId,
            'code' => $this->code,
            'saveCardData' => $this->saveCardData ? true : null,
        ];

        if ($this->cartItems) {
            $data['merchantPaymInfo']['basketOrder'] = array_map(
                fn(CartItemDTO $item) => $item->toArray(),
                $this->cartItems
            );
        }
        
        return array_filter($data, fn($value) => !is_null($value));
    }
}