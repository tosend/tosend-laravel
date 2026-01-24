<?php

namespace ToSend\Laravel\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ToSend\Laravel\Data\EmailResponse;

class EmailResponseTest extends TestCase
{
    public function test_creates_from_array(): void
    {
        $response = EmailResponse::fromArray([
            'message_id' => 'abc123',
        ]);

        $this->assertEquals('abc123', $response->messageId);
    }

    public function test_handles_missing_message_id(): void
    {
        $response = EmailResponse::fromArray([]);

        $this->assertEquals('', $response->messageId);
    }

    public function test_to_array(): void
    {
        $response = new EmailResponse('abc123');

        $this->assertEquals([
            'message_id' => 'abc123',
        ], $response->toArray());
    }
}
