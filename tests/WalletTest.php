<?php

use AratKruglik\Monobank\Facades\Monobank;
use AratKruglik\Monobank\DTO\WalletItem;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['monobank.token' => 'test-token']);
});

it('can get wallet cards', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/wallet?walletId=wallet_1' => Http::response([
            'wallet' => [
                ['cardToken' => 'card_1', 'maskedPan' => '444455**1111', 'country' => 'UKR'],
            ],
        ], 200),
    ]);

    $cards = Monobank::getWalletCards('wallet_1');

    expect($cards)->toBeArray()
        ->and($cards)->toHaveCount(1)
        ->and($cards[0])->toBeInstanceOf(WalletItem::class)
        ->and($cards[0]->cardToken)->toBe('card_1');
});

it('can delete a wallet card via query parameter, not body', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/wallet/card?cardToken=card_1' => Http::response([], 200),
    ]);

    $result = Monobank::deleteWalletCard('card_1');

    expect($result)->toBeTrue();

    Http::assertSent(function ($request) {
        return $request->method() === 'DELETE'
            && str_contains($request->url(), 'cardToken=card_1')
            && $request->data() === [];
    });
});

it('returns empty array when wallet cards key is absent', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/wallet?walletId=wallet_1' => Http::response([], 200),
    ]);

    $cards = Monobank::getWalletCards('wallet_1');

    expect($cards)->toBeArray()->toHaveCount(0);
});

it('unwraps wallet cards specifically under the "wallet" key, not "list" or "checks"', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/wallet?walletId=wallet_1' => Http::response([
            'list' => [
                ['cardToken' => 'wrong_key', 'maskedPan' => '444455**0000', 'country' => 'UKR'],
            ],
            'wallet' => [
                ['cardToken' => 'card_correct', 'maskedPan' => '444455**1111', 'country' => 'UKR'],
            ],
        ], 200),
    ]);

    $cards = Monobank::getWalletCards('wallet_1');

    expect($cards)->toHaveCount(1)
        ->and($cards[0]->cardToken)->toBe('card_correct');
});

it('throws ValidationException when getWalletCards request is invalid', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/wallet?walletId=wallet_1' => Http::response([
            'errCode' => 'INVALID_WALLET',
            'errText' => 'Wallet not found',
        ], 400),
    ]);

    Monobank::getWalletCards('wallet_1');
})->throws(\AratKruglik\Monobank\Exceptions\ValidationException::class);

it('throws ServerException when deleteWalletCard fails', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/wallet/card?cardToken=card_1' => Http::response([], 500),
    ]);

    Monobank::deleteWalletCard('card_1');
})->throws(\AratKruglik\Monobank\Exceptions\ServerException::class);
