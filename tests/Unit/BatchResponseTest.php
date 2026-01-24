<?php

namespace ToSend\Laravel\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ToSend\Laravel\Data\BatchResponse;
use ToSend\Laravel\Data\BatchEmailResult;

class BatchResponseTest extends TestCase
{
    public function test_creates_from_array(): void
    {
        $response = BatchResponse::fromArray([
            'results' => [
                ['status' => 'success', 'message_id' => 'abc123'],
                ['status' => 'error', 'message' => 'Failed'],
            ],
        ]);

        $this->assertCount(2, $response->results);
    }

    public function test_successful_returns_only_successful(): void
    {
        $response = BatchResponse::fromArray([
            'results' => [
                ['status' => 'success', 'message_id' => 'abc'],
                ['status' => 'error', 'message' => 'Failed'],
                ['status' => 'success', 'message_id' => 'def'],
            ],
        ]);

        $successful = $response->successful();

        $this->assertCount(2, $successful);
    }

    public function test_failed_returns_only_failed(): void
    {
        $response = BatchResponse::fromArray([
            'results' => [
                ['status' => 'success', 'message_id' => 'abc'],
                ['status' => 'error', 'message' => 'Failed'],
                ['status' => 'spam', 'message' => 'Spam detected'],
            ],
        ]);

        $failed = $response->failed();

        $this->assertCount(2, $failed);
    }

    public function test_all_successful_returns_true_when_all_succeed(): void
    {
        $response = BatchResponse::fromArray([
            'results' => [
                ['status' => 'success', 'message_id' => 'abc'],
                ['status' => 'success', 'message_id' => 'def'],
            ],
        ]);

        $this->assertTrue($response->allSuccessful());
    }

    public function test_all_successful_returns_false_when_any_fails(): void
    {
        $response = BatchResponse::fromArray([
            'results' => [
                ['status' => 'success', 'message_id' => 'abc'],
                ['status' => 'error', 'message' => 'Failed'],
            ],
        ]);

        $this->assertFalse($response->allSuccessful());
    }

    public function test_success_count(): void
    {
        $response = BatchResponse::fromArray([
            'results' => [
                ['status' => 'success', 'message_id' => 'abc'],
                ['status' => 'error', 'message' => 'Failed'],
                ['status' => 'success', 'message_id' => 'def'],
            ],
        ]);

        $this->assertEquals(2, $response->successCount());
    }

    public function test_failed_count(): void
    {
        $response = BatchResponse::fromArray([
            'results' => [
                ['status' => 'success', 'message_id' => 'abc'],
                ['status' => 'error', 'message' => 'Failed'],
                ['status' => 'spam', 'message' => 'Spam'],
            ],
        ]);

        $this->assertEquals(2, $response->failedCount());
    }
}

class BatchEmailResultTest extends TestCase
{
    public function test_creates_from_array(): void
    {
        $result = BatchEmailResult::fromArray([
            'status' => 'success',
            'message_id' => 'abc123',
        ]);

        $this->assertEquals('success', $result->status);
        $this->assertEquals('abc123', $result->messageId);
    }

    public function test_is_success(): void
    {
        $result = new BatchEmailResult('success', 'abc123');

        $this->assertTrue($result->isSuccess());
        $this->assertFalse($result->isError());
        $this->assertFalse($result->isSpam());
    }

    public function test_is_error(): void
    {
        $result = new BatchEmailResult('error', null, 'Something went wrong');

        $this->assertFalse($result->isSuccess());
        $this->assertTrue($result->isError());
        $this->assertFalse($result->isSpam());
    }

    public function test_is_spam(): void
    {
        $result = new BatchEmailResult('spam', null, 'Spam detected');

        $this->assertFalse($result->isSuccess());
        $this->assertFalse($result->isError());
        $this->assertTrue($result->isSpam());
    }

    public function test_to_array_success(): void
    {
        $result = new BatchEmailResult('success', 'abc123');

        $this->assertEquals([
            'status' => 'success',
            'message_id' => 'abc123',
        ], $result->toArray());
    }

    public function test_to_array_error(): void
    {
        $result = new BatchEmailResult('error', null, 'Failed', ['to' => ['invalid' => 'Invalid email']]);

        $array = $result->toArray();

        $this->assertEquals('error', $array['status']);
        $this->assertEquals('Failed', $array['message']);
        $this->assertArrayHasKey('errors', $array);
    }
}
