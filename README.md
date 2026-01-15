# Monobank Acquiring for Laravel

A robust, idiomatic Laravel package for integrating with the **Monobank Acquiring API**. Designed specifically for e-commerce applications, it provides secure invoice creation, payment processing, refund handling, and webhook verification.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/aratkruglik/monobank-laravel.svg?style=flat-square)](https://packagist.org/packages/aratkruglik/monobank-laravel)
[![Tests](https://github.com/aratkruglik/monobank-laravel/actions/workflows/run-tests.yml/badge.svg?branch=main)](https://github.com/aratkruglik/monobank-laravel/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/aratkruglik/monobank-laravel.svg?style=flat-square)](https://packagist.org/packages/aratkruglik/monobank-laravel)

## Features

*   🚀 **Full Acquiring API Support**: Create invoices, check statuses, process refunds.
*   🔒 **Security First**: Automatic verification of Monobank Webhook signatures (ECDSA) using dynamic Public Key caching.
*   📦 **Strictly Typed**: Uses DTOs and Enums for all requests and responses. No magic arrays.
*   ⚡ **Laravel Native**: Integration with Laravel's Event system, HTTP Client, and Service Container.
*   🛡️ **Security Hardened**: User-friendly error messages that don't expose API internals, sanitized logging, input validation.

## Official Documentation

For detailed information about the Monobank Acquiring API, please refer to the official documentation:
*   [Internet Acquiring API](https://monobank.ua/api-docs/acquiring)
*   [QR Acquiring API](https://monobank.ua/api-docs/acquiring/methods/qr/post--api--merchant--invoice--create)
*   [Recurring Payments API](https://monobank.ua/api-docs/acquiring/methods/subscription/post--api--merchant--subscription--create)
*   [Marketplace / Split Payments API](https://monobank.ua/api-docs/acquiring/methods/split/post--api--merchant--invoice--create)

## Requirements

*   PHP 8.2+
*   Laravel 11.0+

## Installation

You can install the package via composer:

```bash
composer require aratkruglik/monobank-laravel
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag="monobank-config"
```

## Configuration

Add your Monobank Acquiring Token (X-Token) to your `.env` file:

```env
MONOBANK_TOKEN=your_token_here
MONOBANK_REDIRECT_URL=https://your-site.com/checkout/success
MONOBANK_WEBHOOK_URL=https://your-site.com/api/monobank/webhook
```

You can customize the configuration in `config/monobank.php`.

## Usage

### 1. Creating an Invoice

To accept a payment, create an invoice. You can pass the amount as **float** (major units, e.g., `100.50` UAH) or **integer** (cents, e.g., `10050`).

```php
use AratKruglik\Monobank\Facades\Monobank;
use AratKruglik\Monobank\DTO\InvoiceRequestDTO;
use AratKruglik\Monobank\Enums\CurrencyCode;

$invoice = Monobank::createInvoice(new InvoiceRequestDTO(
    amount: 100.00, // 100.00 UAH
    ccy: CurrencyCode::UAH,
    destination: 'Payment for Order #12345',
    reference: 'ORDER-12345',
    redirectUrl: 'https://myshop.com/thank-you',
    successUrl: 'https://myshop.com/success',
    failUrl: 'https://myshop.com/failure',
    webHookUrl: 'https://myshop.com/api/webhook',
    validity: 3600 // 1 hour
));

// Redirect user to the payment page
return redirect($invoice->pageUrl);
```

#### With Cart Items

You can pass detailed cart information for the fiscal check.

```php
use AratKruglik\Monobank\DTO\CartItemDTO;

$cart = [
    new CartItemDTO(name: 'T-Shirt', qty: 2, sum: 50.00, code: 'tshirt-001'), // 50.00 UAH
    new CartItemDTO(name: 'Socks', qty: 1, sum: 2.00, code: 'socks-001'), // 2.00 UAH
];

$request = new InvoiceRequestDTO(
    amount: 102.00, // Total: 102.00 UAH
    cartItems: $cart
);

$invoice = Monobank::createInvoice($request);
```

### 2. Checking Invoice Status

Retrieve the current status of an invoice. The response uses strict Enums.

```php
use AratKruglik\Monobank\Enums\InvoiceStatus;

$status = Monobank::getInvoiceStatus('invoice_id_here');

if ($status->status === InvoiceStatus::SUCCESS) {
    // Order paid!
} elseif ($status->status === InvoiceStatus::FAILURE) {
    // Payment failed
}
```

### 3. Cancelling / Refunding

You can cancel an unpaid invoice or refund a paid one (fully or partially).

```php
// Full refund / Cancel
Monobank::cancelInvoice('invoice_id_here');

// Partial refund (e.g., refunding 50.50 UAH)
Monobank::cancelInvoice(
    invoiceId: 'invoice_id_here',
    amount: 50.50 // 50.50 UAH
);
```

### 4. Merchant Details

Get information about your merchant account.

```php
$details = Monobank::getDetails();
echo $details['merchantName'];
```

## QR Acquiring

You can manage your physical QR stands using the following methods.

### 1. List QR Registers

```php
$list = Monobank::getQrList();

foreach ($list as $qr) {
    echo $qr->qrId . ' - ' . $qr->pageUrl;
}
```

### 2. Get QR Details

```php
$qr = Monobank::getQrDetails('qr_id_here');
echo $qr->shortQrId;
```

### 3. Invoice for QR Stand

To assign an amount to a specific QR stand (so the client scans it and sees the amount), create an invoice passing the `qrId`.

```php
$invoice = Monobank::createInvoice(new InvoiceRequestDTO(
    amount: 150.00,
    qrId: 'qr_id_here'
));
```

### 4. Reset QR Amount

Remove the assigned amount from a QR stand.

```php
Monobank::resetQrAmount('qr_id_here');
```

## Recurring Payments (Subscriptions)

You can manage subscriptions for recurring payments.

### 1. Create Subscription

```php
use AratKruglik\Monobank\DTO\SubscriptionRequestDTO;

$subscription = Monobank::createSubscription(new SubscriptionRequestDTO(
    amount: 100.00, // 100.00 UAH
    interval: '1m', // 1 month
    webHookStatusUrl: 'https://site.com/sub/status',
    redirectUrl: 'https://site.com/thank-you'
));

return redirect($subscription->pageUrl);
```

### 2. Get Subscription Details

```php
$details = Monobank::getSubscriptionDetails('sub_id_here');
```

### 3. Cancel Subscription

```php
Monobank::deleteSubscription('sub_id_here');
```

## Marketplace / Split Payments

You can split a payment between multiple recipients (sub-merchants) by specifying `splitReceiverId` for each item in the cart.

### 1. Get Split Receivers

Retrieve a list of available sub-merchants.

```php
$receivers = Monobank::getSplitReceivers();
```

### 2. Create Split Invoice

```php
use AratKruglik\Monobank\DTO\CartItemDTO;

$cart = [
    new CartItemDTO(
        name: 'Item from Partner A', 
        qty: 1, 
        sum: 100.00, 
        splitReceiverId: 'receiver_id_a'
    ),
    new CartItemDTO(
        name: 'Item from Partner B', 
        qty: 1, 
        sum: 200.00, 
        splitReceiverId: 'receiver_id_b'
    ),
];

$request = new InvoiceRequestDTO(
    amount: 300.00,
    cartItems: $cart
);

$invoice = Monobank::createInvoice($request);
```

## Webhooks

This package handles webhook security automatically. It fetches Monobank's public key, caches it, and verifies the `X-Sign` header for every incoming request.

### 1. Setup Route

Register the webhook route in your `routes/api.php` using the provided macro:

```php
use Illuminate\Support\Facades\Route;

// This registers a POST route that handles signature verification
Route::monobankWebhook('/monobank/webhook');
```

### 2. Handle Events

When a valid webhook is received, the package dispatches the `AratKruglik\Monobank\Events\WebhookReceived` event. You should listen for this event in your application.

**EventServiceProvider:**

```php
use AratKruglik\Monobank\Events\WebhookReceived;
use App\Listeners\HandleMonobankPayment;

protected $listen = [
    WebhookReceived::class => [
        HandleMonobankPayment::class,
    ],
];
```

**Listener:**

```php
namespace App\Listeners;

use AratKruglik\Monobank\Events\WebhookReceived;
use AratKruglik\Monobank\Enums\InvoiceStatus;

class HandleMonobankPayment
{
    public function handle(WebhookReceived $event): void
    {
        $payload = $event->payload;
        
        // $payload is an array containing invoiceId, status, amount, etc.
        $invoiceId = $payload['invoiceId'];
        $status = $payload['status'];

        if ($status === InvoiceStatus::SUCCESS->value) {
            // Mark order as paid
        }
    }
}
```

## Error Handling

The package provides typed exceptions with user-friendly messages that don't expose sensitive API details:

```php
use AratKruglik\Monobank\Exceptions\ValidationException;
use AratKruglik\Monobank\Exceptions\AuthenticationException;
use AratKruglik\Monobank\Exceptions\RateLimitExceededException;
use AratKruglik\Monobank\Exceptions\ServerException;

try {
    $invoice = Monobank::createInvoice($request);
} catch (ValidationException $e) {
    // User-friendly message for display
    $userMessage = $e->getMessage(); // "Payment validation failed..."

    // API details for logging (not exposed to users)
    Log::error('Monobank error', $e->getApiErrorDetails());
} catch (RateLimitExceededException $e) {
    // Retry after the specified time
    $retryAfter = $e->retryAfter; // seconds
} catch (AuthenticationException $e) {
    // Check your API token
}
```

## Testing

The package is fully tested with **Pest**. You can run the tests using:

```bash
composer test
```

## Testing & Development

To test your integration without real money:

1.  Obtain a **Test Acquiring Token** from the [Monobank Dashboard](https://web.monobank.ua/).
2.  Set this token in your `.env` file: `MONOBANK_TOKEN=your_test_token`.
3.  In the test environment, you can use any valid card number (passing Luhn validation) to simulate payments. Financial authorization is skipped.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
