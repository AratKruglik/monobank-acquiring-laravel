<?php

namespace AratKruglik\Monobank\Exceptions;

use Throwable;

class RateLimitExceededException extends MonobankException
{
    public function __construct(
        string $message = "",
        int $code = 429,
        ?Throwable $previous = null,
        public readonly int $retryAfter = 60
    ) {
        parent::__construct($message, $code, $previous, 'RATE_LIMIT', 'Too many requests');
    }
}
