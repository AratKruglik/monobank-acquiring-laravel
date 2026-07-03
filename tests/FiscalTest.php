<?php

use AratKruglik\Monobank\Facades\Monobank;
use AratKruglik\Monobank\DTO\FiscalCheck;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['monobank.token' => 'test-token']);
});

it('can get fiscal checks', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/invoice/fiscal-checks*' => Http::response([
            'checks' => [
                [
                    'id' => 'check_1',
                    'status' => 'done',
                    'type' => 'sale',
                    'fiscalizationSource' => 'checkbox',
                    'taxUrl' => 'https://cabinet.tax.gov.ua/check_1',
                ],
            ],
        ], 200),
    ]);

    $checks = Monobank::getFiscalChecks('inv_123');

    expect($checks)->toBeArray()
        ->and($checks)->toHaveCount(1)
        ->and($checks[0])->toBeInstanceOf(FiscalCheck::class)
        ->and($checks[0]->status)->toBe('done');
});

it('can get a receipt', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/invoice/receipt*' => Http::response([
            'file' => 'base64-encoded-content',
        ], 200),
    ]);

    $file = Monobank::getReceipt('inv_123');

    expect($file)->toBe('base64-encoded-content');
});

it('returns null receipt when file absent', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/invoice/receipt*' => Http::response([], 200),
    ]);

    $file = Monobank::getReceipt('inv_123');

    expect($file)->toBeNull();
});

it('sends email as query param when requesting a receipt', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/invoice/receipt*' => Http::response([
            'file' => 'base64-encoded-content',
        ], 200),
    ]);

    Monobank::getReceipt('inv_123', 'buyer@example.com');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'invoiceId=inv_123')
            && str_contains($request->url(), 'email=buyer%40example.com');
    });
});

it('returns empty array when fiscal checks key is absent', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/invoice/fiscal-checks*' => Http::response([], 200),
    ]);

    $checks = Monobank::getFiscalChecks('inv_123');

    expect($checks)->toBeArray()->toHaveCount(0);
});

it('unwraps fiscal checks specifically under the "checks" key, not "list" or "wallet"', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/invoice/fiscal-checks*' => Http::response([
            'list' => [
                ['id' => 'wrong_key', 'status' => 'done', 'type' => 'sale', 'fiscalizationSource' => 'checkbox', 'taxUrl' => 'x'],
            ],
            'checks' => [
                ['id' => 'check_correct', 'status' => 'done', 'type' => 'sale', 'fiscalizationSource' => 'checkbox', 'taxUrl' => 'x'],
            ],
        ], 200),
    ]);

    $checks = Monobank::getFiscalChecks('inv_123');

    expect($checks)->toHaveCount(1)
        ->and($checks[0]->id)->toBe('check_correct');
});

it('throws ValidationException when getFiscalChecks fails', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/invoice/fiscal-checks*' => Http::response([
            'errCode' => 'NOT_FOUND',
            'errText' => 'Invoice not found',
        ], 400),
    ]);

    Monobank::getFiscalChecks('inv_missing');
})->throws(\AratKruglik\Monobank\Exceptions\ValidationException::class);

it('throws AuthenticationException when getReceipt fails with 401', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/invoice/receipt*' => Http::response([], 401),
    ]);

    Monobank::getReceipt('inv_123');
})->throws(\AratKruglik\Monobank\Exceptions\AuthenticationException::class);
