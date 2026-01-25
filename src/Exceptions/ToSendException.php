<?php

namespace ToSend\Laravel\Exceptions;

use Exception;

class ToSendException extends Exception
{
    protected array $errors;
    protected string $errorType;

    /**
     * Error type constants.
     */
    public const TYPE_BAD_REQUEST = 'bad_request';
    public const TYPE_UNAUTHORIZED = 'unauthorized';
    public const TYPE_FORBIDDEN = 'forbidden';
    public const TYPE_NOT_FOUND = 'not_found';
    public const TYPE_VALIDATION_ERROR = 'validation_error';
    public const TYPE_RATE_LIMIT_EXCEEDED = 'rate_limit_exceeded';
    public const TYPE_INTERNAL_ERROR = 'internal_error';

    public function __construct(
        string $message,
        int $code = 0,
        array $errors = [],
        string $errorType = '',
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
        $this->errorType = $errorType ?: $this->getDefaultErrorType($code);
    }

    /**
     * Get validation errors.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get the error type.
     */
    public function getErrorType(): string
    {
        return $this->errorType;
    }

    /**
     * Check if this is a validation error.
     */
    public function isValidationError(): bool
    {
        return $this->errorType === self::TYPE_VALIDATION_ERROR || $this->code === 422;
    }

    /**
     * Check if this is an authentication error.
     */
    public function isAuthenticationError(): bool
    {
        return in_array($this->errorType, [self::TYPE_UNAUTHORIZED, self::TYPE_FORBIDDEN])
            || in_array($this->code, [401, 403]);
    }

    /**
     * Check if this is a rate limit error.
     */
    public function isRateLimitError(): bool
    {
        return $this->errorType === self::TYPE_RATE_LIMIT_EXCEEDED || $this->code === 429;
    }

    /**
     * Create exception from API response.
     */
    public static function fromResponse(array $response, int $statusCode): self
    {
        return new self(
            message: $response['message'] ?? 'Unknown error',
            code: $statusCode,
            errors: $response['errors'] ?? [],
            errorType: $response['error_type'] ?? ''
        );
    }

    /**
     * Get default error type based on status code.
     */
    protected function getDefaultErrorType(int $code): string
    {
        return match ($code) {
            400 => self::TYPE_BAD_REQUEST,
            401 => self::TYPE_UNAUTHORIZED,
            403 => self::TYPE_FORBIDDEN,
            404 => self::TYPE_NOT_FOUND,
            422 => self::TYPE_VALIDATION_ERROR,
            429 => self::TYPE_RATE_LIMIT_EXCEEDED,
            default => self::TYPE_INTERNAL_ERROR,
        };
    }
}
