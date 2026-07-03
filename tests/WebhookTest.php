<?php

use AratKruglik\Monobank\Facades\Monobank;
use AratKruglik\Monobank\Services\PubKeyProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->webhookRoute = Route::monobankWebhook('/monobank/webhook');
});

it('validates webhook signature and dispatches event', function () {
    Event::fake();

    // 1. Generate Key Pair
    $keyRes = openssl_pkey_new([
        "private_key_bits" => 2048,
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
    ]);
    openssl_pkey_export($keyRes, $privateKey);
    $publicKey = openssl_pkey_get_details($keyRes)['key'];

    // 2. Mock PubKey Endpoint
    Http::fake([
        'api.monobank.ua/api/merchant/pubkey' => Http::response(['key' => base64_encode($publicKey)], 200),
    ]);

    // Clear cache to force fetch
    Cache::forget(PubKeyProvider::CACHE_KEY);

    // 3. Prepare Payload and Signature
    $payload = json_encode(['invoiceId' => 'inv_123', 'status' => 'success']);
    openssl_sign($payload, $signature, $privateKey, OPENSSL_ALGO_SHA256);
    $xSign = base64_encode($signature);

    // 4. Send Request
    $response = $this->postJson('/monobank/webhook', json_decode($payload, true), [
        'X-Sign' => $xSign
    ]);

    // 5. Assertions
    $response->assertOk();
    
    Event::assertDispatched(\AratKruglik\Monobank\Events\WebhookReceived::class, function ($event) {
        return $event->payload['invoiceId'] === 'inv_123';
    });
});

it('rejects webhook with invalid signature', function () {
    Event::fake();

    // Mock PubKey (any valid key)
    $keyRes = openssl_pkey_new(["private_key_bits" => 2048, "private_key_type" => OPENSSL_KEYTYPE_RSA]);
    $publicKey = openssl_pkey_get_details($keyRes)['key'];

    Http::fake([
        'api.monobank.ua/api/merchant/pubkey' => Http::response(['key' => base64_encode($publicKey)], 200),
    ]);
    Cache::forget(PubKeyProvider::CACHE_KEY);

    $payload = json_encode(['invoiceId' => 'inv_fake']);
    $xSign = base64_encode('invalid_signature_data');

    $response = $this->postJson('/monobank/webhook', json_decode($payload, true), [
        'X-Sign' => $xSign
    ]);

    $response->assertStatus(403);
    $response->assertJson(['error' => 'Unauthorized']);
    Event::assertNotDispatched(\AratKruglik\Monobank\Events\WebhookReceived::class);
});

it('rejects webhook with missing X-Sign header', function () {
    Event::fake();

    $response = $this->postJson('/monobank/webhook', ['invoiceId' => 'inv_123']);

    $response->assertStatus(403);
    $response->assertJson(['error' => 'Unauthorized']);
    Event::assertNotDispatched(\AratKruglik\Monobank\Events\WebhookReceived::class);
});

it('rejects webhook with invalid base64 signature', function () {
    Event::fake();

    // Mock PubKey
    $keyRes = openssl_pkey_new(["private_key_bits" => 2048, "private_key_type" => OPENSSL_KEYTYPE_RSA]);
    $publicKey = openssl_pkey_get_details($keyRes)['key'];

    Http::fake([
        'api.monobank.ua/api/merchant/pubkey' => Http::response(['key' => base64_encode($publicKey)], 200),
    ]);
    Cache::forget(PubKeyProvider::CACHE_KEY);

    // Send request with invalid base64 (not valid base64 characters)
    $response = $this->postJson('/monobank/webhook', ['invoiceId' => 'inv_123'], [
        'X-Sign' => '!!!invalid-base64!!!'
    ]);

    $response->assertStatus(403);
    $response->assertJson(['error' => 'Unauthorized']);
    Event::assertNotDispatched(\AratKruglik\Monobank\Events\WebhookReceived::class);
});

it('returns unified error message for all auth failures to prevent timing attacks', function () {
    Event::fake();

    // Test missing signature
    $response1 = $this->postJson('/monobank/webhook', ['invoiceId' => 'inv_1']);

    // Test invalid base64
    $response2 = $this->postJson('/monobank/webhook', ['invoiceId' => 'inv_2'], [
        'X-Sign' => '!!!invalid!!!'
    ]);

    // Both should return the same error message
    expect($response1->json('error'))->toBe('Unauthorized');
    expect($response2->json('error'))->toBe('Unauthorized');
    expect($response1->status())->toBe(403);
    expect($response2->status())->toBe(403);
});

it('retries with a fresh key and succeeds when the cached key is stale', function () {
    Event::fake();

    $oldKeyRes = openssl_pkey_new(["private_key_bits" => 2048, "private_key_type" => OPENSSL_KEYTYPE_RSA]);
    $oldPublicKey = openssl_pkey_get_details($oldKeyRes)['key'];

    $newKeyRes = openssl_pkey_new(["private_key_bits" => 2048, "private_key_type" => OPENSSL_KEYTYPE_RSA]);
    openssl_pkey_export($newKeyRes, $newPrivateKey);
    $newPublicKey = openssl_pkey_get_details($newKeyRes)['key'];

    Http::fake([
        'api.monobank.ua/api/merchant/pubkey' => Http::sequence()
            ->push(['key' => base64_encode($oldPublicKey)], 200)
            ->push(['key' => base64_encode($newPublicKey)], 200),
    ]);

    Cache::forget(PubKeyProvider::CACHE_KEY);

    $payload = json_encode(['invoiceId' => 'inv_123', 'status' => 'success']);
    openssl_sign($payload, $signature, $newPrivateKey, OPENSSL_ALGO_SHA256);
    $xSign = base64_encode($signature);

    $response = $this->postJson('/monobank/webhook', json_decode($payload, true), [
        'X-Sign' => $xSign,
    ]);

    $response->assertOk();
    Http::assertSentCount(2);

    Event::assertDispatched(\AratKruglik\Monobank\Events\WebhookReceived::class, function ($event) {
        return $event->payload['invoiceId'] === 'inv_123';
    });
});

it('rejects webhook when both the cached and refetched keys fail verification', function () {
    Event::fake();

    $oldKeyRes = openssl_pkey_new(["private_key_bits" => 2048, "private_key_type" => OPENSSL_KEYTYPE_RSA]);
    $oldPublicKey = openssl_pkey_get_details($oldKeyRes)['key'];

    $newKeyRes = openssl_pkey_new(["private_key_bits" => 2048, "private_key_type" => OPENSSL_KEYTYPE_RSA]);
    $newPublicKey = openssl_pkey_get_details($newKeyRes)['key'];

    $unrelatedKeyRes = openssl_pkey_new(["private_key_bits" => 2048, "private_key_type" => OPENSSL_KEYTYPE_RSA]);
    openssl_pkey_export($unrelatedKeyRes, $unrelatedPrivateKey);

    Http::fake([
        'api.monobank.ua/api/merchant/pubkey' => Http::sequence()
            ->push(['key' => base64_encode($oldPublicKey)], 200)
            ->push(['key' => base64_encode($newPublicKey)], 200),
    ]);

    Cache::forget(PubKeyProvider::CACHE_KEY);

    $payload = json_encode(['invoiceId' => 'inv_123', 'status' => 'success']);
    openssl_sign($payload, $signature, $unrelatedPrivateKey, OPENSSL_ALGO_SHA256);
    $xSign = base64_encode($signature);

    $response = $this->postJson('/monobank/webhook', json_decode($payload, true), [
        'X-Sign' => $xSign,
    ]);

    $response->assertStatus(403);
    $response->assertJson(['error' => 'Unauthorized']);
    Event::assertNotDispatched(\AratKruglik\Monobank\Events\WebhookReceived::class);
});

it('excludes query string parameters from the dispatched payload', function () {
    Event::fake();

    $keyRes = openssl_pkey_new(["private_key_bits" => 2048, "private_key_type" => OPENSSL_KEYTYPE_RSA]);
    openssl_pkey_export($keyRes, $privateKey);
    $publicKey = openssl_pkey_get_details($keyRes)['key'];

    Http::fake([
        'api.monobank.ua/api/merchant/pubkey' => Http::response(['key' => base64_encode($publicKey)], 200),
    ]);

    Cache::forget(PubKeyProvider::CACHE_KEY);

    $payload = json_encode(['invoiceId' => 'inv_123', 'status' => 'success']);
    openssl_sign($payload, $signature, $privateKey, OPENSSL_ALGO_SHA256);
    $xSign = base64_encode($signature);

    $response = $this->call('POST', '/monobank/webhook?injected=evil', [], [], [], [
        'HTTP_X-Sign' => $xSign,
        'CONTENT_TYPE' => 'application/json',
    ], $payload);

    $response->assertOk();

    Event::assertDispatched(\AratKruglik\Monobank\Events\WebhookReceived::class, function ($event) {
        return ! array_key_exists('injected', $event->payload)
            && $event->payload['invoiceId'] === 'inv_123';
    });
});

it('returns 503 when a ConnectionException is thrown while refetching the key during retry', function () {
    Event::fake();

    $oldKeyRes = openssl_pkey_new(["private_key_bits" => 2048, "private_key_type" => OPENSSL_KEYTYPE_RSA]);
    $oldPublicKey = openssl_pkey_get_details($oldKeyRes)['key'];

    $callCount = 0;
    Http::fake([
        'api.monobank.ua/api/merchant/pubkey' => function () use (&$callCount, $oldPublicKey) {
            $callCount++;

            if ($callCount === 1) {
                return Http::response(['key' => base64_encode($oldPublicKey)], 200);
            }

            throw new \Illuminate\Http\Client\ConnectionException('Connection refused');
        },
    ]);

    Cache::forget(PubKeyProvider::CACHE_KEY);

    $payload = json_encode(['invoiceId' => 'inv_123', 'status' => 'success']);
    $xSign = base64_encode('some_signature_that_will_not_verify_against_old_key');

    $response = $this->postJson('/monobank/webhook', json_decode($payload, true), [
        'X-Sign' => $xSign,
    ]);

    $response->assertStatus(503);
    expect($callCount)->toBe(2);
    Event::assertNotDispatched(\AratKruglik\Monobank\Events\WebhookReceived::class);
});

it('returns 500 when the public key endpoint returns an invalid key', function () {
    Event::fake();

    Http::fake([
        'api.monobank.ua/api/merchant/pubkey' => Http::response(['key' => 'not-a-valid-key'], 200),
    ]);

    Cache::forget(PubKeyProvider::CACHE_KEY);

    $payload = json_encode(['invoiceId' => 'inv_123', 'status' => 'success']);
    $xSign = base64_encode('irrelevant_signature');

    $response = $this->postJson('/monobank/webhook', json_decode($payload, true), [
        'X-Sign' => $xSign,
    ]);

    $response->assertStatus(500);
    Event::assertNotDispatched(\AratKruglik\Monobank\Events\WebhookReceived::class);
});

it('dispatches the body value, not the query string value, when a query parameter collides with a body field name', function () {
    Event::fake();

    $keyRes = openssl_pkey_new(["private_key_bits" => 2048, "private_key_type" => OPENSSL_KEYTYPE_RSA]);
    openssl_pkey_export($keyRes, $privateKey);
    $publicKey = openssl_pkey_get_details($keyRes)['key'];

    Http::fake([
        'api.monobank.ua/api/merchant/pubkey' => Http::response(['key' => base64_encode($publicKey)], 200),
    ]);

    Cache::forget(PubKeyProvider::CACHE_KEY);

    $payload = json_encode(['invoiceId' => 'inv_from_body', 'status' => 'success']);
    openssl_sign($payload, $signature, $privateKey, OPENSSL_ALGO_SHA256);
    $xSign = base64_encode($signature);

    $response = $this->call('POST', '/monobank/webhook?invoiceId=inv_from_query', [], [], [], [
        'HTTP_X-Sign' => $xSign,
        'CONTENT_TYPE' => 'application/json',
    ], $payload);

    $response->assertOk();

    Event::assertDispatched(\AratKruglik\Monobank\Events\WebhookReceived::class, function ($event) {
        return $event->payload['invoiceId'] === 'inv_from_body';
    });
});

it('applies the configured middleware to the webhook route macro', function () {
    config(['monobank.webhook.middleware' => ['custom-mw']]);

    $customRoute = Route::monobankWebhook('/monobank/webhook-custom-mw-test');

    expect($customRoute->gatherMiddleware())->toContain('custom-mw');
});

it('applies the default middleware to the webhook route macro when config is left at package default', function () {
    expect($this->webhookRoute->gatherMiddleware())->toContain('api');
});
