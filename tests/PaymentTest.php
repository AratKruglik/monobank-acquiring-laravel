<?php

use AratKruglik\Monobank\Facades\Monobank;
use AratKruglik\Monobank\DTO\PaymentDirectRequestDTO;
use AratKruglik\Monobank\DTO\SyncPaymentRequestDTO;
use AratKruglik\Monobank\DTO\WalletPaymentRequestDTO;
use AratKruglik\Monobank\DTO\PaymentResponseDTO;
use AratKruglik\Monobank\DTO\InvoiceStatusDTO;
use AratKruglik\Monobank\Enums\InvoiceStatus;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['monobank.token' => 'test-token']);
});

it('can process a direct payment', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/invoice/payment-direct' => Http::response([
            'invoiceId' => 'inv_direct',
            'status' => 'processing',
            'amount' => 10000,
            'ccy' => 980,
            'tdsUrl' => 'https://tds.example.com',
        ], 200),
    ]);

    $request = new PaymentDirectRequestDTO(
        amount: 100.00,
        cardData: ['pan' => '4242424242424242', 'exp' => '1230', 'cvv' => '123'],
    );

    $response = Monobank::paymentDirect($request);

    expect($response)->toBeInstanceOf(PaymentResponseDTO::class)
        ->and($response->invoiceId)->toBe('inv_direct')
        ->and($response->status)->toBe(InvoiceStatus::PROCESSING)
        ->and($response->tdsUrl)->toBe('https://tds.example.com');

    Http::assertSent(function ($request) {
        return $request['amount'] === 10000 && $request['cardData']['pan'] === '4242424242424242';
    });
});

it('can process a direct payment without failureReason or tdsUrl', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/invoice/payment-direct' => Http::response([
            'invoiceId' => 'inv_direct_2',
            'status' => 'success',
            'amount' => 10000,
            'ccy' => 980,
        ], 200),
    ]);

    $request = new PaymentDirectRequestDTO(
        amount: 100.00,
        cardData: ['pan' => '4242424242424242', 'exp' => '1230', 'cvv' => '123'],
    );

    $response = Monobank::paymentDirect($request);

    expect($response->failureReason)->toBeNull()
        ->and($response->tdsUrl)->toBeNull();
});

it('can process a direct payment with a failureReason present', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/invoice/payment-direct' => Http::response([
            'invoiceId' => 'inv_direct_3',
            'status' => 'failure',
            'amount' => 10000,
            'ccy' => 980,
            'failureReason' => 'Insufficient funds',
        ], 200),
    ]);

    $request = new PaymentDirectRequestDTO(
        amount: 100.00,
        cardData: ['pan' => '4242424242424242', 'exp' => '1230', 'cvv' => '123'],
    );

    $response = Monobank::paymentDirect($request);

    expect($response->status)->toBe(InvoiceStatus::FAILURE)
        ->and($response->failureReason)->toBe('Insufficient funds');
});

it('can process a sync payment', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/invoice/sync-payment' => Http::response([
            'invoiceId' => 'inv_sync',
            'status' => 'success',
            'amount' => 5000,
            'ccy' => 980,
        ], 200),
    ]);

    $request = new SyncPaymentRequestDTO(
        amount: 50.00,
        cardData: ['pan' => '4242424242424242', 'exp' => '1230', 'cvv' => '123'],
    );

    $response = Monobank::syncPayment($request);

    expect($response)->toBeInstanceOf(InvoiceStatusDTO::class)
        ->and($response->status)->toBe(InvoiceStatus::SUCCESS)
        ->and($response->paymentInfo)->toBeNull()
        ->and($response->cancelList)->toBe([])
        ->and($response->tipsInfo)->toBeNull()
        ->and($response->walletData)->toBeNull();
});

it('can process a sync payment via applePay', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/invoice/sync-payment' => Http::response([
            'invoiceId' => 'inv_sync_ap',
            'status' => 'success',
            'amount' => 5000,
            'ccy' => 980,
        ], 200),
    ]);

    $request = new SyncPaymentRequestDTO(
        amount: 50.00,
        applePay: ['token' => 'abc'],
    );

    $response = Monobank::syncPayment($request);

    expect($response->invoiceId)->toBe('inv_sync_ap');

    Http::assertSent(function ($request) {
        return $request['applePay']['token'] === 'abc' && ! isset($request['cardData']);
    });
});

it('populates all new InvoiceStatusDTO fields when present in sync payment response', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/invoice/sync-payment' => Http::response([
            'invoiceId' => 'inv_sync_full',
            'status' => 'success',
            'amount' => 5000,
            'ccy' => 980,
            'paymentInfo' => ['maskedPan' => '444455**1111', 'approvalCode' => '123456'],
            'cancelList' => [
                ['status' => 'processing', 'amount' => 5000, 'ccy' => 980, 'createdDate' => '2024-01-01T12:00:00Z'],
            ],
            'tipsInfo' => ['employeeId' => 'emp_1', 'amount' => 100],
            'walletData' => ['cardToken' => 'card_1', 'status' => 'created'],
        ], 200),
    ]);

    $request = new SyncPaymentRequestDTO(
        amount: 50.00,
        cardData: ['pan' => '4242424242424242', 'exp' => '1230', 'cvv' => '123'],
    );

    $response = Monobank::syncPayment($request);

    expect($response->paymentInfo)->toBe(['maskedPan' => '444455**1111', 'approvalCode' => '123456'])
        ->and($response->cancelList)->toHaveCount(1)
        ->and($response->cancelList[0]['status'])->toBe('processing')
        ->and($response->tipsInfo)->toBe(['employeeId' => 'emp_1', 'amount' => 100])
        ->and($response->walletData)->toBe(['cardToken' => 'card_1', 'status' => 'created']);
});

it('throws if sync payment has no card source', function () {
    new SyncPaymentRequestDTO(amount: 50.00);
})->throws(InvalidArgumentException::class);

it('throws if sync payment has more than one card source', function () {
    new SyncPaymentRequestDTO(
        amount: 50.00,
        cardData: ['pan' => '4242424242424242', 'exp' => '1230', 'cvv' => '123'],
        applePay: ['token' => 'abc'],
    );
})->throws(InvalidArgumentException::class);

it('can process a wallet payment', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/wallet/payment' => Http::response([
            'invoiceId' => 'inv_wallet',
            'status' => 'success',
            'amount' => 2000,
            'ccy' => 980,
        ], 200),
    ]);

    $request = new WalletPaymentRequestDTO(
        cardToken: 'card_token_1',
        amount: 20.00,
        initiationKind: 'merchant',
    );

    $response = Monobank::walletPayment($request);

    expect($response)->toBeInstanceOf(PaymentResponseDTO::class)
        ->and($response->invoiceId)->toBe('inv_wallet');

    Http::assertSent(function ($request) {
        return $request['cardToken'] === 'card_token_1' && $request['initiationKind'] === 'merchant';
    });
});

it('can process a wallet payment with tdsUrl present', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/wallet/payment' => Http::response([
            'invoiceId' => 'inv_wallet_3ds',
            'status' => 'processing',
            'amount' => 2000,
            'ccy' => 980,
            'tdsUrl' => 'https://tds.example.com/3ds',
        ], 200),
    ]);

    $request = new WalletPaymentRequestDTO(
        cardToken: 'card_token_1',
        amount: 20.00,
        initiationKind: 'merchant',
    );

    $response = Monobank::walletPayment($request);

    expect($response->tdsUrl)->toBe('https://tds.example.com/3ds')
        ->and($response->failureReason)->toBeNull();
});

it('throws ValidationException when paymentDirect fails', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/invoice/payment-direct' => Http::response([
            'errCode' => 'INVALID_CARD',
            'errText' => 'Card data is invalid',
        ], 400),
    ]);

    $request = new PaymentDirectRequestDTO(
        amount: 100.00,
        cardData: ['pan' => '4242424242424242', 'exp' => '1230', 'cvv' => '123'],
    );

    Monobank::paymentDirect($request);
})->throws(\AratKruglik\Monobank\Exceptions\ValidationException::class);

it('throws ServerException when syncPayment fails', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/invoice/sync-payment' => Http::response([], 503),
    ]);

    $request = new SyncPaymentRequestDTO(
        amount: 50.00,
        cardData: ['pan' => '4242424242424242', 'exp' => '1230', 'cvv' => '123'],
    );

    Monobank::syncPayment($request);
})->throws(\AratKruglik\Monobank\Exceptions\ServerException::class);

it('throws AuthenticationException when walletPayment fails', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/wallet/payment' => Http::response([], 401),
    ]);

    $request = new WalletPaymentRequestDTO(
        cardToken: 'card_token_1',
        amount: 20.00,
        initiationKind: 'merchant',
    );

    Monobank::walletPayment($request);
})->throws(\AratKruglik\Monobank\Exceptions\AuthenticationException::class);
