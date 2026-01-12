<?php

namespace AratKruglik\Monobank\DTO;

use AratKruglik\Monobank\Enums\CurrencyCode;

readonly class InvoiceRequestDTO
{
    /**
     * @param int $amount Amount in cents (minimal unit)
     * @param BasketItemDTO[]|null $basketOrder
     */
    public function __construct(
        public int $amount,
        public int|CurrencyCode $ccy = 980, // UAH by default
        public ?array $merchantPaymInfo = null,
        public ?string $redirectUrl = null,
        public ?string $webHookUrl = null,
        public ?int $validity = 3600,
        public ?string $paymentType = 'debit',
        public ?string $qrId = null,
        public ?string $code = null,
        public ?array $basketOrder = null, // Array of BasketItemDTO
        public bool $saveCardData = false
    ) {}

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

        if ($this->basketOrder) {
            $data['merchantPaymInfo']['basketOrder'] = array_map(
                fn(BasketItemDTO $item) => $item->toArray(),
                $this->basketOrder
            );
        }
        
        // Remove explicit saveCardData if false to avoid sending unnecessary fields, 
        // though API might treat null/missing as false.
        
        return array_filter($data, fn($value) => !is_null($value));
    }
}
