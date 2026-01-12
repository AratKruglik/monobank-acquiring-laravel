<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Monobank Acquiring Token
    |--------------------------------------------------------------------------
    |
    | The "X-Token" required for accessing the Merchant API.
    | Obtain this from your Monobank Acquiring dashboard.
    |
    */
    'token' => env('MONOBANK_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Merchant Configuration
    |--------------------------------------------------------------------------
    |
    | Default redirect URL and callback URL for invoices.
    |
    */
    'redirect_url' => env('MONOBANK_REDIRECT_URL'),
    'webhook_url' => env('MONOBANK_WEBHOOK_URL'), // Full URL to your controller

    /*
    |--------------------------------------------------------------------------
    | Webhooks & Security
    |--------------------------------------------------------------------------
    |
    | Configuration for handling incoming webhooks.
    |
    */
    'webhook' => [
        'path' => '/monobank/webhook', // Default route path if using macro
        'middleware' => ['api'],
    ],
];