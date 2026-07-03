<?php

use AratKruglik\Monobank\Facades\Monobank;
use AratKruglik\Monobank\DTO\StatementItem;
use AratKruglik\Monobank\DTO\EmployeeItem;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['monobank.token' => 'test-token']);
});

it('can get a statement', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/statement*' => Http::response([
            'list' => [
                [
                    'invoiceId' => 'inv_1',
                    'status' => 'success',
                    'maskedPan' => '444455**1111',
                    'date' => '2024-01-01T12:00:00Z',
                    'amount' => 1000,
                    'ccy' => 980,
                    'paymentScheme' => 'MasterCard',
                ],
            ],
        ], 200),
    ]);

    $statement = Monobank::getStatement(1704067200, 1704153600);

    expect($statement)->toBeArray()
        ->and($statement)->toHaveCount(1)
        ->and($statement[0])->toBeInstanceOf(StatementItem::class)
        ->and($statement[0]->invoiceId)->toBe('inv_1');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'from=1704067200')
            && str_contains($request->url(), 'to=1704153600');
    });
});

it('can get a statement with only from', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/statement*' => Http::response([
            'list' => [],
        ], 200),
    ]);

    $statement = Monobank::getStatement(1704067200);

    expect($statement)->toBeArray()->toHaveCount(0);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'from=1704067200')
            && ! str_contains($request->url(), 'to=')
            && ! str_contains($request->url(), 'code=');
    });
});

it('can get a statement with from and code', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/statement*' => Http::response([
            'list' => [],
        ], 200),
    ]);

    Monobank::getStatement(1704067200, null, 'qr_1');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'from=1704067200')
            && str_contains($request->url(), 'code=qr_1')
            && ! str_contains($request->url(), 'to=');
    });
});

it('can get a statement with from, to, and code', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/statement*' => Http::response([
            'list' => [],
        ], 200),
    ]);

    Monobank::getStatement(1704067200, 1704153600, 'qr_1');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'from=1704067200')
            && str_contains($request->url(), 'to=1704153600')
            && str_contains($request->url(), 'code=qr_1');
    });
});

it('returns empty array when statement list key is absent', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/statement*' => Http::response([], 200),
    ]);

    $statement = Monobank::getStatement(1704067200);

    expect($statement)->toBeArray()->toHaveCount(0);
});

it('can get employee list', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/employee/list' => Http::response([
            'list' => [
                ['id' => 'emp_1', 'name' => 'John Doe', 'extRef' => 'ext_1'],
            ],
        ], 200),
    ]);

    $employees = Monobank::getEmployeeList();

    expect($employees)->toBeArray()
        ->and($employees)->toHaveCount(1)
        ->and($employees[0])->toBeInstanceOf(EmployeeItem::class)
        ->and($employees[0]->name)->toBe('John Doe');
});

it('returns empty array when employee list key is absent', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/employee/list' => Http::response([], 200),
    ]);

    $employees = Monobank::getEmployeeList();

    expect($employees)->toBeArray()->toHaveCount(0);
});

it('throws ValidationException when getStatement request is invalid', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/statement*' => Http::response([
            'errCode' => 'BAD_REQUEST',
            'errText' => 'Invalid period',
        ], 400),
    ]);

    Monobank::getStatement(1704067200);
})->throws(\AratKruglik\Monobank\Exceptions\ValidationException::class);

it('throws ServerException when employee list endpoint fails', function () {
    Http::fake([
        'api.monobank.ua/api/merchant/employee/list' => Http::response([], 500),
    ]);

    Monobank::getEmployeeList();
})->throws(\AratKruglik\Monobank\Exceptions\ServerException::class);
