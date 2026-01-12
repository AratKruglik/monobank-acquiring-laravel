<?php

namespace AratKruglik\Monobank;

use AratKruglik\Monobank\Exceptions\AuthenticationException;
use AratKruglik\Monobank\Exceptions\ConnectionException;
use AratKruglik\Monobank\Exceptions\MonobankException;
use AratKruglik\Monobank\Exceptions\RateLimitExceededException;
use AratKruglik\Monobank\Exceptions\ServerException;
use AratKruglik\Monobank\Exceptions\ValidationException;
use Illuminate\Http\Client\ConnectionException as LaravelConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class Client
{
    protected string $baseUrl = 'https://api.monobank.ua/api/merchant/';

    public function __construct(protected array $config)
    {
    }

    public function get(string $uri, array $query = []): Response
    {
        return $this->request('get', $uri, $query);
    }

    public function post(string $uri, array $data = []): Response
    {
        return $this->request('post', $uri, $data);
    }

    protected function request(string $method, string $uri, array $data = []): Response
    {
        try {
            $response = $this->makeRequest()->$method($uri, $data);
        } catch (LaravelConnectionException $e) {
            throw new ConnectionException($e->getMessage(), $e->getCode(), $e);
        }

        if ($response->successful()) {
            return $response;
        }

        $this->handleError($response);

        return $response;
    }

    protected function makeRequest(): PendingRequest
    {
        $request = Http::baseUrl($this->baseUrl)
            ->timeout(30)
            ->asJson()
            ->acceptJson();

        if (!empty($this->config['token'])) {
            $request->withHeader('X-Token', $this->config['token']);
        }

        return $request;
    }

    protected function handleError(Response $response): void
    {
        $status = $response->status();
        $message = $response->json('errCode') . ': ' . $response->json('errText') ?? $response->body();

        match ($status) {
            400 => throw new ValidationException("Monobank API Error (400): $message", 400),
            401, 403 => throw new AuthenticationException("Monobank API Unauthorized (401/403): $message", $status),
            429 => throw new RateLimitExceededException(
                "Monobank API Rate Limit Exceeded (429)",
                429,
                null,
                (int) $response->header('Retry-After', 60)
            ),
            500, 502, 503, 504 => throw new ServerException("Monobank Server Error ($status): $message", $status),
            default => throw new MonobankException("Monobank API Unknown Error ($status): $message", $status),
        };
    }
}