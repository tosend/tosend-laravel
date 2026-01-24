<?php

namespace ToSend\Laravel\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ToSend\Laravel\Exceptions\ToSendException;

class ToSendExceptionTest extends TestCase
{
    public function test_creates_exception_with_message(): void
    {
        $exception = new ToSendException('Something went wrong');

        $this->assertEquals('Something went wrong', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertEquals([], $exception->getErrors());
    }

    public function test_creates_exception_with_code_and_errors(): void
    {
        $errors = [
            'from' => ['required' => 'From is required'],
            'subject' => ['required' => 'Subject is required'],
        ];

        $exception = new ToSendException('Validation failed', 422, $errors);

        $this->assertEquals('Validation failed', $exception->getMessage());
        $this->assertEquals(422, $exception->getCode());
        $this->assertEquals($errors, $exception->getErrors());
    }

    public function test_is_validation_error(): void
    {
        $exception = new ToSendException('Validation failed', 422);

        $this->assertTrue($exception->isValidationError());
        $this->assertFalse($exception->isAuthenticationError());
        $this->assertFalse($exception->isRateLimitError());
    }

    public function test_is_authentication_error_401(): void
    {
        $exception = new ToSendException('Unauthorized', 401);

        $this->assertFalse($exception->isValidationError());
        $this->assertTrue($exception->isAuthenticationError());
        $this->assertFalse($exception->isRateLimitError());
    }

    public function test_is_authentication_error_403(): void
    {
        $exception = new ToSendException('Forbidden', 403);

        $this->assertFalse($exception->isValidationError());
        $this->assertTrue($exception->isAuthenticationError());
        $this->assertFalse($exception->isRateLimitError());
    }

    public function test_is_rate_limit_error(): void
    {
        $exception = new ToSendException('Too many requests', 429);

        $this->assertFalse($exception->isValidationError());
        $this->assertFalse($exception->isAuthenticationError());
        $this->assertTrue($exception->isRateLimitError());
    }

    public function test_from_response(): void
    {
        $response = [
            'message' => 'Invalid API key',
            'errors' => [
                'api_key' => ['invalid' => 'The provided API key is invalid'],
            ],
        ];

        $exception = ToSendException::fromResponse($response, 403);

        $this->assertEquals('Invalid API key', $exception->getMessage());
        $this->assertEquals(403, $exception->getCode());
        $this->assertArrayHasKey('api_key', $exception->getErrors());
    }

    public function test_from_response_handles_missing_fields(): void
    {
        $exception = ToSendException::fromResponse([], 500);

        $this->assertEquals('Unknown error', $exception->getMessage());
        $this->assertEquals(500, $exception->getCode());
        $this->assertEquals([], $exception->getErrors());
    }
}
