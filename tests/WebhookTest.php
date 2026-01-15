<?php

use AratKruglik\Monobank\Facades\Monobank;
use AratKruglik\Monobank\Services\PubKeyProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Route::monobankWebhook('/monobank/webhook');
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
        'api.monobank.ua/api/merchant/pubkey' => Http::response(['key' => $publicKey], 200),
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
        'api.monobank.ua/api/merchant/pubkey' => Http::response(['key' => $publicKey], 200),
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
        'api.monobank.ua/api/merchant/pubkey' => Http::response(['key' => $publicKey], 200),
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
