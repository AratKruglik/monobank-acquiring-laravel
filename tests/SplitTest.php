<?php

use AratKruglik\Monobank\Exceptions\AuthenticationException;
use AratKruglik\Monobank\Facades\Monobank;
use AratKruglik\Monobank\DTO\InvoiceRequestDTO;
use AratKruglik\Monobank\DTO\CartItemDTO;
use AratKruglik\Monobank\DTO\Submerchant;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['monobank.token' => 'test-token']);
});

it('can create a split invoice', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/invoice/create' => Http::response([
            'invoiceId' => 'inv_split',
            'pageUrl' => 'https://pay.mb.ua/inv_split',
        ], 200),
    ]);

    $cart = [
        new CartItemDTO(name: 'Item 1', qty: 1, sum: 10.00, splitReceiverId: 'receiver_1'),
        new CartItemDTO(name: 'Item 2', qty: 1, sum: 20.00, splitReceiverId: 'receiver_2'),
    ];

    $request = new InvoiceRequestDTO(
        amount: 30.00,
        cartItems: $cart
    );

    Monobank::createInvoice($request);

    Http::assertSent(function ($request) {
        $items = $request['merchantPaymInfo']['basketOrder'];
        return count($items) === 2 &&
               $items[0]['splitReceiverId'] === 'receiver_1' &&
               $items[1]['splitReceiverId'] === 'receiver_2';
    });
});

it('can get split receivers', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/submerchant/list' => Http::response([
            'list' => [
                ['code' => 'code_1', 'iban' => 'UA111111111111111111111111111', 'edrpou' => '123', 'owner' => 'Owner 1'],
                ['code' => 'code_2', 'iban' => 'UA222222222222222222222222222', 'edrpou' => '456', 'owner' => 'Owner 2'],
            ]
        ], 200),
    ]);

    $receivers = Monobank::getSplitReceivers();

    expect($receivers)->toBeArray()
        ->and($receivers)->toHaveCount(2)
        ->and($receivers[0])->toBeInstanceOf(Submerchant::class)
        ->and($receivers[0]->code)->toBe('code_1')
        ->and($receivers[0]->iban)->toBe('UA111111111111111111111111111');

    Http::assertSent(function ($request) {
        return $request->method() === 'GET'
            && str_contains($request->url(), 'submerchant/list');
    });
});

it('returns empty array when split receivers list key is absent', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/submerchant/list' => Http::response([], 200),
    ]);

    $receivers = Monobank::getSplitReceivers();

    expect($receivers)->toBeArray()->toHaveCount(0);
});

it('throws AuthenticationException when getSplitReceivers fails with 401', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/submerchant/list' => Http::response([], 401),
    ]);

    Monobank::getSplitReceivers();
})->throws(AuthenticationException::class);
