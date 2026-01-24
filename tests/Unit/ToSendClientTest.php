<?php

namespace ToSend\Laravel\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ToSend\Laravel\ToSend;
use ToSend\Laravel\Exceptions\ToSendException;

class ToSendClientTest extends TestCase
{
    public function test_throws_exception_when_api_key_is_empty(): void
    {
        $this->expectException(ToSendException::class);
        $this->expectExceptionMessage('API key is required');
        $this->expectExceptionCode(401);

        new ToSend('');
    }

    public function test_throws_exception_when_api_key_is_whitespace(): void
    {
        $this->expectException(ToSendException::class);
        $this->expectExceptionMessage('API key is required');

        new ToSend('   ');
    }

    public function test_creates_client_with_valid_api_key(): void
    {
        $client = new ToSend('tsend_valid_api_key');

        $this->assertInstanceOf(ToSend::class, $client);
    }

    public function test_send_validates_missing_from(): void
    {
        $client = new ToSend('tsend_test_key');

        $this->expectException(ToSendException::class);
        $this->expectExceptionCode(422);

        $client->send([
            'to' => [['email' => 'test@example.com']],
            'subject' => 'Test',
            'html' => '<p>Hi</p>',
        ]);
    }

    public function test_send_validates_invalid_from_email(): void
    {
        $client = new ToSend('tsend_test_key');

        $this->expectException(ToSendException::class);

        $client->send([
            'from' => ['email' => 'not-an-email'],
            'to' => [['email' => 'test@example.com']],
            'subject' => 'Test',
            'html' => '<p>Hi</p>',
        ]);
    }

    public function test_send_validates_missing_to(): void
    {
        $client = new ToSend('tsend_test_key');

        $this->expectException(ToSendException::class);

        $client->send([
            'from' => ['email' => 'sender@example.com'],
            'subject' => 'Test',
            'html' => '<p>Hi</p>',
        ]);
    }

    public function test_send_validates_invalid_to(): void
    {
        $client = new ToSend('tsend_test_key');

        $this->expectException(ToSendException::class);

        $client->send([
            'from' => ['email' => 'sender@example.com'],
            'to' => [['email' => 'invalid']],
            'subject' => 'Test',
            'html' => '<p>Hi</p>',
        ]);
    }

    public function test_send_validates_missing_subject(): void
    {
        $client = new ToSend('tsend_test_key');

        $this->expectException(ToSendException::class);

        $client->send([
            'from' => ['email' => 'sender@example.com'],
            'to' => [['email' => 'test@example.com']],
            'html' => '<p>Hi</p>',
        ]);
    }

    public function test_send_validates_missing_content(): void
    {
        $client = new ToSend('tsend_test_key');

        $this->expectException(ToSendException::class);

        $client->send([
            'from' => ['email' => 'sender@example.com'],
            'to' => [['email' => 'test@example.com']],
            'subject' => 'Test',
        ]);
    }

    public function test_send_accepts_text_content(): void
    {
        // This test verifies validation passes with text only
        // It will fail at the HTTP request level which is expected
        $client = new ToSend('tsend_test_key');
        $client = new class('tsend_test_key') extends ToSend {
            protected function request(string $method, string $endpoint, array $data = []): array
            {
                return ['message_id' => 'test123'];
            }
        };

        $response = $client->send([
            'from' => ['email' => 'sender@example.com'],
            'to' => [['email' => 'test@example.com']],
            'subject' => 'Test',
            'text' => 'Plain text content',
        ]);

        $this->assertEquals('test123', $response->messageId);
    }

    public function test_batch_validates_empty_array(): void
    {
        $client = new ToSend('tsend_test_key');

        $this->expectException(ToSendException::class);
        $this->expectExceptionMessage('Emails array is required and cannot be empty');

        $client->batch([]);
    }

    public function test_batch_validates_each_email(): void
    {
        $client = new ToSend('tsend_test_key');

        $this->expectException(ToSendException::class);
        $this->expectExceptionMessage('Email at index 1:');

        $client->batch([
            [
                'from' => ['email' => 'sender@example.com'],
                'to' => [['email' => 'test@example.com']],
                'subject' => 'Valid',
                'html' => '<p>Hi</p>',
            ],
            [
                'from' => ['email' => 'sender@example.com'],
                'to' => [['email' => 'test@example.com']],
                // Missing subject and content
            ],
        ]);
    }
}
