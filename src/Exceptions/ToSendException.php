<?php

namespace ToSend\Laravel\Exceptions;

use Exception;

class ToSendException extends Exception
{
    protected array $errors;

    public function __construct(string $message, int $code = 0, array $errors = [], ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    /**
     * Get validation errors.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Check if this is a validation error.
     */
    public function isValidationError(): bool
    {
        return $this->code === 422;
    }

    /**
     * Check if this is an authentication error.
     */
    public function isAuthenticationError(): bool
    {
        return in_array($this->code, [401, 403]);
    }

    /**
     * Check if this is a rate limit error.
     */
    public function isRateLimitError(): bool
    {
        return $this->code === 429;
    }

    /**
     * Create exception from API response.
     */
    public static function fromResponse(array $response, int $statusCode): self
    {
        return new self(
            message: $response['message'] ?? 'Unknown error',
            code: $statusCode,
            errors: $response['errors'] ?? []
        );
    }
}
