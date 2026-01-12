<?php

use AratKruglik\Monobank\Facades\Monobank;
use AratKruglik\Monobank\DTO\QrDetailsDTO;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['monobank.token' => 'test-token']);
});

it('can get list of QR registers', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/qr/list' => Http::response([
            [
                'qrId' => 'qr1',
                'shortQrId' => 'short1',
                'paymentType' => 'debit',
                'pageUrl' => 'https://pay.mb.ua/qr1',
            ],
            [
                'qrId' => 'qr2',
                'shortQrId' => 'short2',
                'paymentType' => 'debit',
                'pageUrl' => 'https://pay.mb.ua/qr2',
            ]
        ], 200),
    ]);

    $list = Monobank::getQrList();

    expect($list)->toBeArray()
        ->and($list)->toHaveCount(2)
        ->and($list[0])->toBeInstanceOf(QrDetailsDTO::class)
        ->and($list[0]->qrId)->toBe('qr1');
});

it('can get qr details', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/qr/details/qr1' => Http::response([
            'qrId' => 'qr1',
            'shortQrId' => 'short1',
            'paymentType' => 'debit',
            'pageUrl' => 'https://pay.mb.ua/qr1',
            'amount' => '1000',
            'ccy' => 980
        ], 200),
    ]);

    $details = Monobank::getQrDetails('qr1');

    expect($details)->toBeInstanceOf(QrDetailsDTO::class)
        ->and($details->qrId)->toBe('qr1')
        ->and($details->amount)->toBe('1000');
});

it('can reset qr amount', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/qr/reset-amount' => Http::response([], 200),
    ]);

    $result = Monobank::resetQrAmount('qr1');

    expect($result)->toBeTrue();
    
    Http::assertSent(function ($request) {
        return $request['qrId'] === 'qr1';
    });
});
