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

it('can create an invoice', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/invoice/create' => Http::response([
            'invoiceId' => 'inv_123',
            'pageUrl' => 'https://pay.mb.ua/inv_123',
        ], 200),
    ]);

    $request = new InvoiceRequestDTO(
        amount: 10000, // 100.00 UAH
        redirectUrl: 'https://example.com/success',
        webHookUrl: 'https://example.com/webhook'
    );

    $response = Monobank::merchant()->createInvoice($request);

    expect($response)->toBeInstanceOf(InvoiceResponseDTO::class)
        ->and($response->invoiceId)->toBe('inv_123')
        ->and($response->pageUrl)->toBe('https://pay.mb.ua/inv_123');
    
    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.monobank.ua/api/merchant/invoice/create' &&
               $request['amount'] === 10000 &&
               $request['ccy'] === 980 && // Verify default int is sent
               $request->header('X-Token')[0] === 'test-token';
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

    $status = Monobank::merchant()->getInvoiceStatus('inv_123');

    expect($status)->toBeInstanceOf(InvoiceStatusDTO::class)
        ->and($status->status)->toBe(InvoiceStatus::SUCCESS) // Enum assertion
        ->and($status->ccy)->toBe(CurrencyCode::UAH) // Enum assertion
        ->and($status->finalAmount)->toBe(10000);
});

it('can cancel an invoice', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/invoice/cancel' => Http::response(['status' => 'ok'], 200),
    ]);

    $result = Monobank::merchant()->cancelInvoice('inv_123');

    expect($result)->toBeTrue();
});

it('can get merchant details', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/details' => Http::response([
            'merchantId' => 'm_123',
            'merchantName' => 'Test Shop'
        ], 200),
    ]);

    $details = Monobank::merchant()->getDetails();

    expect($details)->toBeArray()
        ->and($details['merchantId'])->toBe('m_123');
});
