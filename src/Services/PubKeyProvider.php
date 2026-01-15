<?php

namespace AratKruglik\Monobank\Services;

use AratKruglik\Monobank\Contracts\ClientInterface;
use AratKruglik\Monobank\Exceptions\ValidationException;
use Illuminate\Support\Facades\Cache;

class PubKeyProvider
{
    public const CACHE_KEY = 'monobank_acquiring_pubkey';
    public const TTL = 3600; // 1 hour (reduced from 24h for security)

    public function __construct(protected ClientInterface $client)
    {
    }

    /**
     * Get the Monobank public key for webhook signature verification.
     *
     * @throws ValidationException If the public key is missing from the response
     */
    public function getKey(): string
    {
        return Cache::remember(self::CACHE_KEY, self::TTL, function () {
            $response = $this->client->get('pubkey');
            $key = $response->json('key');

            if (empty($key)) {
                throw new ValidationException('Failed to retrieve public key from Monobank API');
            }

            return $key;
        });
    }

    /**
     * Clear the cached public key.
     */
    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
