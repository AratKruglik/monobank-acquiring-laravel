<?php

use AratKruglik\Monobank\Facades\Monobank;
use AratKruglik\Monobank\DTO\InvoiceRequestDTO;
use AratKruglik\Monobank\DTO\CartItemDTO;
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
        'api.monobank.ua/api/merchant/split-receivers' => Http::response([
            'receivers' => [
                ['id' => 'receiver_1', 'name' => 'Partner 1'],
                ['id' => 'receiver_2', 'name' => 'Partner 2'],
            ]
        ], 200),
    ]);

    $receivers = Monobank::getSplitReceivers();

    expect($receivers)->toBeArray()
        ->and($receivers['receivers'])->toHaveCount(2);
});
