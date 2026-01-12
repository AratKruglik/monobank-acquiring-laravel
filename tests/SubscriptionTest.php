<?php

use AratKruglik\Monobank\Facades\Monobank;
use AratKruglik\Monobank\DTO\SubscriptionRequestDTO;
use AratKruglik\Monobank\DTO\SubscriptionResponseDTO;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['monobank.token' => 'test-token']);
});

it('can create a subscription', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/subscription/create' => Http::response([
            'subscriptionId' => 'sub_123',
            'pageUrl' => 'https://pay.mb.ua/sub_123',
        ], 200),
    ]);

    $request = new SubscriptionRequestDTO(
        amount: 100.00,
        interval: '1m',
        webHookStatusUrl: 'https://site.com/status',
        redirectUrl: 'https://site.com/success'
    );

    $response = Monobank::createSubscription($request);

    expect($response)->toBeInstanceOf(SubscriptionResponseDTO::class)
        ->and($response->subscriptionId)->toBe('sub_123');
    
    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.monobank.ua/api/merchant/subscription/create' &&
               $request['amount'] === 10000 &&
               $request['interval'] === '1m';
    });
});

it('can get subscription details', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/subscription/status?subscriptionId=sub_123' => Http::response([
            'subscriptionId' => 'sub_123',
            'status' => 'active',
        ], 200),
    ]);

    $details = Monobank::getSubscriptionDetails('sub_123');

    expect($details)->toBeArray()
        ->and($details['status'])->toBe('active');
});

it('can delete subscription', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/subscription/delete' => Http::response([], 200),
    ]);

    $result = Monobank::deleteSubscription('sub_123');

    expect($result)->toBeTrue();
});
