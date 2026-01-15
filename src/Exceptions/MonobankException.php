<?php

namespace AratKruglik\Monobank\Exceptions;

use Exception;
use Throwable;

class MonobankException extends Exception
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        protected ?string $errorCode = null,
        protected ?string $errorText = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the Monobank API error code.
     */
    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    /**
     * Get the Monobank API error text.
     */
    public function getErrorText(): ?string
    {
        return $this->errorText;
    }

    /**
     * Get full API error details for logging purposes.
     */
    public function getApiErrorDetails(): array
    {
        return [
            'errorCode' => $this->errorCode,
            'errorText' => $this->errorText,
            'httpCode' => $this->code,
        ];
    }
}
