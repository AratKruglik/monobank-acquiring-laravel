<?php

use AratKruglik\Monobank\Client;
use AratKruglik\Monobank\Exceptions\AuthenticationException;
use AratKruglik\Monobank\Exceptions\MonobankException;
use AratKruglik\Monobank\Exceptions\RateLimitExceededException;
use AratKruglik\Monobank\Exceptions\ServerException;
use AratKruglik\Monobank\Exceptions\ValidationException;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->client = new Client(['token' => 'test-token']);
});

it('throws ValidationException on 400 response', function () {
    Http::fake([
        '*' => Http::response([
            'errCode' => 'INVALID_AMOUNT',
            'errText' => 'Amount must be positive',
        ], 400),
    ]);

    $this->client->post('invoice/create', ['amount' => -100]);
})->throws(ValidationException::class, 'Payment validation failed');

it('throws AuthenticationException on 401 response', function () {
    Http::fake([
        '*' => Http::response([
            'errCode' => 'INVALID_TOKEN',
            'errText' => 'Token is invalid or expired',
        ], 401),
    ]);

    $this->client->get('details');
})->throws(AuthenticationException::class, 'Authentication failed');

it('throws AuthenticationException on 403 response', function () {
    Http::fake([
        '*' => Http::response([
            'errCode' => 'FORBIDDEN',
            'errText' => 'Access denied',
        ], 403),
    ]);

    $this->client->get('details');
})->throws(AuthenticationException::class, 'Authentication failed');

it('throws RateLimitExceededException on 429 response', function () {
    Http::fake([
        '*' => Http::response([], 429, ['Retry-After' => '120']),
    ]);

    try {
        $this->client->get('details');
    } catch (RateLimitExceededException $e) {
        expect($e->retryAfter)->toBe(120);
        expect($e->getMessage())->toContain('Rate limit exceeded');
        throw $e;
    }
})->throws(RateLimitExceededException::class);

it('throws ServerException on 500 response', function () {
    Http::fake([
        '*' => Http::response([
            'errCode' => 'INTERNAL_ERROR',
            'errText' => 'Something went wrong',
        ], 500),
    ]);

    $this->client->get('details');
})->throws(ServerException::class, 'Payment service temporarily unavailable');

it('throws ServerException on 502 response', function () {
    Http::fake([
        '*' => Http::response([], 502),
    ]);

    $this->client->get('details');
})->throws(ServerException::class);

it('throws ServerException on 503 response', function () {
    Http::fake([
        '*' => Http::response([], 503),
    ]);

    $this->client->get('details');
})->throws(ServerException::class);

it('throws MonobankException on unknown error status', function () {
    Http::fake([
        '*' => Http::response(['errCode' => 'UNKNOWN'], 418),
    ]);

    $this->client->get('details');
})->throws(MonobankException::class, 'An unexpected error occurred');

it('stores API error details in exception', function () {
    Http::fake([
        '*' => Http::response([
            'errCode' => 'INVALID_INVOICE',
            'errText' => 'Invoice not found',
        ], 400),
    ]);

    try {
        $this->client->get('invoice/status', ['invoiceId' => 'invalid']);
    } catch (ValidationException $e) {
        expect($e->getErrorCode())->toBe('INVALID_INVOICE');
        expect($e->getErrorText())->toBe('Invoice not found');
        expect($e->getApiErrorDetails())->toBe([
            'errorCode' => 'INVALID_INVOICE',
            'errorText' => 'Invoice not found',
            'httpCode' => 400,
        ]);
    }
});

it('does not expose API details in exception message', function () {
    Http::fake([
        '*' => Http::response([
            'errCode' => 'SECRET_ERROR_CODE',
            'errText' => 'Internal secret message with sensitive data',
        ], 400),
    ]);

    try {
        $this->client->get('details');
    } catch (ValidationException $e) {
        // Message should be user-friendly, not contain API details
        expect($e->getMessage())->not->toContain('SECRET_ERROR_CODE');
        expect($e->getMessage())->not->toContain('sensitive data');
        expect($e->getMessage())->toBe('Payment validation failed. Please check your request parameters.');
    }
});
