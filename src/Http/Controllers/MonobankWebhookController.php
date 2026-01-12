<?php

namespace AratKruglik\Monobank\Http\Controllers;

use AratKruglik\Monobank\Events\WebhookReceived;
use AratKruglik\Monobank\Exceptions\MonobankException;
use AratKruglik\Monobank\Services\PubKeyProvider;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class MonobankWebhookController extends Controller
{
    public function __construct(protected PubKeyProvider $pubKeyProvider)
    {
    }

    public function __invoke(Request $request)
    {
        $xSign = $request->header('X-Sign');

        if (! $xSign) {
            Log::warning('Monobank Webhook: Missing X-Sign header');
            return response()->json(['error' => 'Missing Signature'], 403);
        }

        try {
            $pubKey = $this->pubKeyProvider->getKey();
        } catch (\Exception $e) {
             Log::error('Monobank Webhook: Failed to fetch public key', ['error' => $e->getMessage()]);
             return response()->json(['error' => 'Key Fetch Error'], 500);
        }

        $signature = base64_decode($xSign);
        $data = $request->getContent(); // Raw body

        $isValid = openssl_verify($data, $signature, $pubKey, OPENSSL_ALGO_SHA256);

        if ($isValid !== 1) {
             Log::warning('Monobank Webhook: Invalid Signature');
             return response()->json(['error' => 'Invalid Signature'], 403);
        }

        Log::info('Monobank Webhook Received & Verified', ['payload' => $request->all()]);

        WebhookReceived::dispatch($request->all());

        return response()->json(['status' => 'ok']);
    }
}