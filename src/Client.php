<?php

namespace AratKruglik\Monobank;

use AratKruglik\Monobank\Contracts\ClientInterface;
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
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class Client implements ClientInterface
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

        if (! $response->successful()) {
            $this->handleError($response);
        }

        return $response;
    }

    protected function makeRequest(): PendingRequest
    {
        $request = Http::baseUrl($this->baseUrl)
            ->timeout(30)
            ->asJson()
            ->acceptJson();

        if (! empty($this->config['token'])) {
            $request->withHeader('X-Token', $this->config['token']);
        }

        return $request;
    }

    protected function handleError(Response $response): never
    {
        $status = $response->status();
        $errCode = $response->json('errCode') ?? 'UNKNOWN';
        $errText = $response->json('errText') ?? $response->body();

        match ($status) {
            HttpStatus::HTTP_BAD_REQUEST => throw new ValidationException(
                "Payment validation failed. Please check your request parameters.",
                400,
                null,
                $errCode,
                $errText
            ),
            HttpStatus::HTTP_UNAUTHORIZED, HttpStatus::HTTP_FORBIDDEN => throw new AuthenticationException(
                "Authentication failed. Please verify your API token.",
                $status,
                null,
                $errCode,
                $errText
            ),
            HttpStatus::HTTP_TOO_MANY_REQUESTS => throw new RateLimitExceededException(
                "Rate limit exceeded. Please try again later.",
                429,
                null,
                (int) $response->header('Retry-After', 60)
            ),
            HttpStatus::HTTP_INTERNAL_SERVER_ERROR,
            HttpStatus::HTTP_BAD_GATEWAY,
            HttpStatus::HTTP_SERVICE_UNAVAILABLE,
            HttpStatus::HTTP_GATEWAY_TIMEOUT => throw new ServerException(
                "Payment service temporarily unavailable. Please try again later.",
                $status,
                null,
                $errCode,
                $errText
            ),
            default => throw new MonobankException(
                "An unexpected error occurred during payment processing.",
                $status,
                null,
                $errCode,
                $errText
            ),
        };
    }
}