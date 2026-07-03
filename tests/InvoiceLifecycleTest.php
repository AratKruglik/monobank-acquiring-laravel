<?php

use AratKruglik\Monobank\Facades\Monobank;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['monobank.token' => 'test-token']);
});

it('can remove an invoice', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/invoice/remove' => Http::response([], 200),
    ]);

    $result = Monobank::removeInvoice('inv_123');

    expect($result)->toBeTrue();

    Http::assertSent(function ($request) {
        return $request['invoiceId'] === 'inv_123';
    });
});

it('can finalize an invoice with float amount', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/invoice/finalize' => Http::response(['status' => 'success'], 200),
    ]);

    $status = Monobank::finalizeInvoice('inv_123', 50.50);

    expect($status)->toBe('success');

    Http::assertSent(function ($request) {
        return $request['invoiceId'] === 'inv_123' && $request['amount'] === 5050;
    });
});

it('can finalize an invoice without amount', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/invoice/finalize' => Http::response(['status' => 'success'], 200),
    ]);

    $status = Monobank::finalizeInvoice('inv_123');

    expect($status)->toBe('success');

    Http::assertSent(function ($request) {
        return $request['invoiceId'] === 'inv_123' && ! isset($request['amount']);
    });
});

it('can finalize an invoice with items and int amount', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/invoice/finalize' => Http::response(['status' => 'success'], 200),
    ]);

    $items = [['name' => 'Item 1', 'qty' => 1, 'sum' => 5050]];

    $status = Monobank::finalizeInvoice('inv_123', 5050, $items);

    expect($status)->toBe('success');

    Http::assertSent(function ($request) use ($items) {
        return $request['invoiceId'] === 'inv_123'
            && $request['amount'] === 5050
            && $request['items'] === $items;
    });
});

it('can remove an invoice regardless of response body', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/invoice/remove' => Http::response(null, 200),
    ]);

    $result = Monobank::removeInvoice('inv_456');

    expect($result)->toBeTrue();
});

it('throws ValidationException when removeInvoice fails', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/invoice/remove' => Http::response([
            'errCode' => 'NOT_FOUND',
            'errText' => 'Invoice not found',
        ], 400),
    ]);

    Monobank::removeInvoice('inv_missing');
})->throws(\AratKruglik\Monobank\Exceptions\ValidationException::class);

it('throws ServerException when finalizeInvoice fails', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/invoice/finalize' => Http::response([], 500),
    ]);

    Monobank::finalizeInvoice('inv_123');
})->throws(\AratKruglik\Monobank\Exceptions\ServerException::class);
