<?php

use AratKruglik\Monobank\Facades\Monobank;
use AratKruglik\Monobank\DTO\InvoiceRequestDTO;
use AratKruglik\Monobank\DTO\InvoiceResponseDTO;
use AratKruglik\Monobank\DTO\InvoiceStatusDTO;
use Illuminate\Support\Facades\Http;

use AratKruglik\Monobank\Enums\InvoiceStatus;
use AratKruglik\Monobank\Enums\CurrencyCode;

beforeEach(function () {
    config(['monobank.token' => 'test-token']);
});

it('can create an invoice with float amount', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/invoice/create' => Http::response([
            'invoiceId' => 'inv_float',
            'pageUrl' => 'https://pay.mb.ua/inv_float',
        ], 200),
    ]);

    // Request with float (100.50 UAH)
    $request = new InvoiceRequestDTO(
        amount: 100.50,
        redirectUrl: 'https://example.com/success',
        webHookUrl: 'https://example.com/webhook'
    );

    $response = Monobank::createInvoice($request);

    expect($response)->toBeInstanceOf(InvoiceResponseDTO::class)
        ->and($response->invoiceId)->toBe('inv_float');
    
    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.monobank.ua/api/merchant/invoice/create' &&
               $request['amount'] === 10050 && // Expecting 10050 cents
               $request->header('X-Token')[0] === 'test-token';
    });
});

it('can create an invoice with int amount', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/invoice/create' => Http::response([
            'invoiceId' => 'inv_123',
            'pageUrl' => 'https://pay.mb.ua/inv_123',
        ], 200),
    ]);

    // Request with int (10000 cents)
    $request = new InvoiceRequestDTO(
        amount: 10000, 
        redirectUrl: 'https://example.com/success',
        webHookUrl: 'https://example.com/webhook'
    );

    $response = Monobank::createInvoice($request);

    expect($response)->toBeInstanceOf(InvoiceResponseDTO::class);
    
    Http::assertSent(function ($request) {
        return $request['amount'] === 10000;
    });
});

it('can check invoice status', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/invoice/status?invoiceId=inv_123' => Http::response([
            'invoiceId' => 'inv_123',
            'status' => 'success',
            'amount' => 10000,
            'ccy' => 980,
            'finalAmount' => 10000,
            'createdDate' => '2023-01-01T12:00:00Z',
        ], 200),
    ]);

    $status = Monobank::getInvoiceStatus('inv_123');

    expect($status)->toBeInstanceOf(InvoiceStatusDTO::class)
        ->and($status->status)->toBe(InvoiceStatus::SUCCESS)
        ->and($status->ccy)->toBe(CurrencyCode::UAH)
        ->and($status->finalAmount)->toBe(10000);
});

it('can cancel an invoice with float amount', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/invoice/cancel' => Http::response(['status' => 'ok'], 200),
    ]);

    // Refund 50.50 UAH
    $result = Monobank::cancelInvoice('inv_123', null, 50.50);

    expect($result)->toBeTrue();

    Http::assertSent(function ($request) {
        return $request['amount'] === 5050;
    });
});

it('can get merchant details', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/details' => Http::response([
            'merchantId' => 'm_123',
            'merchantName' => 'Test Shop'
        ], 200),
    ]);

    $details = Monobank::getDetails();

    expect($details)->toBeArray()
        ->and($details['merchantId'])->toBe('m_123');
});

it('can create an invoice with cart items', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/invoice/create' => Http::response([
            'invoiceId' => 'inv_cart',
            'pageUrl' => 'https://pay.mb.ua/inv_cart',
        ], 200),
    ]);

    $cart = [
        new \AratKruglik\Monobank\DTO\CartItemDTO(name: 'Item 1', qty: 1, sum: 10.00),
    ];

    $request = new InvoiceRequestDTO(
        amount: 10.00,
        cartItems: $cart
    );

    Monobank::createInvoice($request);

    Http::assertSent(function ($request) {
        return isset($request['merchantPaymInfo']['basketOrder']) &&
               $request['merchantPaymInfo']['basketOrder'][0]['name'] === 'Item 1' &&
               $request['merchantPaymInfo']['basketOrder'][0]['sum'] === 1000;
    });
});