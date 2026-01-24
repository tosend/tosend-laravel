<?php

namespace ToSend\Laravel\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ToSend\Laravel\Data\Attachment;

class AttachmentTest extends TestCase
{
    public function test_creates_attachment(): void
    {
        $attachment = new Attachment(
            content: base64_encode('test content'),
            name: 'test.txt',
            type: 'text/plain'
        );

        $this->assertEquals('test.txt', $attachment->name);
        $this->assertEquals('text/plain', $attachment->type);
        $this->assertEquals(base64_encode('test content'), $attachment->content);
    }

    public function test_from_content_encodes_to_base64(): void
    {
        $attachment = Attachment::fromContent(
            content: 'Hello World',
            name: 'hello.txt',
            type: 'text/plain'
        );

        $this->assertEquals('hello.txt', $attachment->name);
        $this->assertEquals('text/plain', $attachment->type);
        $this->assertEquals(base64_encode('Hello World'), $attachment->content);
    }

    public function test_from_base64_preserves_content(): void
    {
        $base64 = base64_encode('Already encoded');

        $attachment = Attachment::fromBase64(
            base64Content: $base64,
            name: 'file.bin',
            type: 'application/octet-stream'
        );

        $this->assertEquals($base64, $attachment->content);
        $this->assertEquals('file.bin', $attachment->name);
    }

    public function test_default_type_is_octet_stream(): void
    {
        $attachment = Attachment::fromContent('data', 'file.bin');

        $this->assertEquals('application/octet-stream', $attachment->type);
    }

    public function test_to_array(): void
    {
        $attachment = new Attachment(
            content: 'Y29udGVudA==',
            name: 'file.pdf',
            type: 'application/pdf'
        );

        $this->assertEquals([
            'content' => 'Y29udGVudA==',
            'name' => 'file.pdf',
            'type' => 'application/pdf',
        ], $attachment->toArray());
    }
}
