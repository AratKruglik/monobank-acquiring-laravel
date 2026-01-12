<?php

namespace AratKruglik\Monobank\Services;

use AratKruglik\Monobank\Client;
use Illuminate\Support\Facades\Cache;

class PubKeyProvider
{
    const CACHE_KEY = 'monobank_acquiring_pubkey';
    const TTL = 86400; // 24 hours

    public function __construct(protected Client $client)
    {
    }

    public function getKey(): string
    {
        return Cache::remember(self::CACHE_KEY, self::TTL, function () {
            $response = $this->client->get('pubkey');
            return $response->json('key');
        });
    }
}
