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
            $encodedKey = $response->json('key');

            if (empty($encodedKey)) {
                throw new ValidationException('Failed to retrieve public key from Monobank API');
            }

            $pem = base64_decode($encodedKey, true);

            if ($pem === false || openssl_pkey_get_public($pem) === false) {
                throw new ValidationException('Monobank API returned an invalid public key');
            }

            return $pem;
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
