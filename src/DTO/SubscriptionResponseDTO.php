<?php

namespace AratKruglik\Monobank\DTO;

readonly class SubscriptionResponseDTO
{
    public function __construct(
        public string $subscriptionId,
        public string $pageUrl
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            subscriptionId: (string) $data['subscriptionId'],
            pageUrl: (string) $data['pageUrl']
        );
    }
}
