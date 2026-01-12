<?php

namespace AratKruglik\Monobank;

use AratKruglik\Monobank\Resources\MerchantResource;

class Monobank
{
    protected Client $client;
    protected ?MerchantResource $merchant = null;

    public function __construct(protected array $config)
    {
        $this->client = new Client($config);
    }

    /**
     * Get the Monobank Acquiring Token.
     */
    public function getToken(): ?string
    {
        return $this->config['token'] ?? null;
    }

    public function merchant(): MerchantResource
    {
        if (! $this->merchant) {
            $this->merchant = new MerchantResource($this->client);
        }

        return $this->merchant;
    }
}
