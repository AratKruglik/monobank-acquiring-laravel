<?php

namespace AratKruglik\Monobank\Http\Controllers;

use AratKruglik\Monobank\Events\WebhookReceived;
use AratKruglik\Monobank\Exceptions\ConnectionException;
use AratKruglik\Monobank\Services\PubKeyProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class MonobankWebhookController extends Controller
{
    public function __construct(protected PubKeyProvider $pubKeyProvider)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $xSign = $request->header('X-Sign');

        if (! $xSign) {
            return $this->unauthorizedResponse($request, 'missing_signature');
        }

        $signature = base64_decode($xSign, true);
        if ($signature === false) {
            return $this->unauthorizedResponse($request, 'invalid_base64');
        }

        try {
            $pubKey = $this->pubKeyProvider->getKey();
        } catch (ConnectionException $e) {
            Log::error('Monobank Webhook: Connection error fetching public key');
            return response()->json(['error' => 'Service temporarily unavailable'], 503);
        } catch (\Exception $e) {
            Log::critical('Monobank Webhook: Unexpected error fetching public key', [
                'exception' => $e::class,
            ]);
            return response()->json(['error' => 'Internal server error'], 500);
        }

        $data = $request->getContent();
        $isValid = openssl_verify($data, $signature, $pubKey, OPENSSL_ALGO_SHA256);

        if ($isValid !== 1) {
            return $this->unauthorizedResponse($request, 'invalid_signature');
        }

        Log::info('Monobank Webhook Received & Verified', [
            'invoiceId' => $request->input('invoiceId'),
            'status' => $request->input('status'),
        ]);

        WebhookReceived::dispatch($request->all());

        return response()->json(['status' => 'ok']);
    }

    private function unauthorizedResponse(Request $request, string $reason): JsonResponse
    {
        Log::warning('Monobank Webhook: Authentication failed', [
            'reason' => $reason,
            'ip' => $request->ip(),
        ]);

        return response()->json(['error' => 'Unauthorized'], 403);
    }
}