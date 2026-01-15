<?php

namespace AratKruglik\Monobank\Contracts;

use Illuminate\Http\Client\Response;

interface ClientInterface
{
    /**
     * Make a GET request to the Monobank API.
     */
    public function get(string $uri, array $query = []): Response;

    /**
     * Make a POST request to the Monobank API.
     */
    public function post(string $uri, array $data = []): Response;
}
