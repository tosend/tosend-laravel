<?php

namespace ToSend\Laravel\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ToSend\Laravel\Data\Email;
use ToSend\Laravel\Data\Address;
use ToSend\Laravel\Data\Attachment;

class EmailTest extends TestCase
{
    public function test_creates_email_with_make(): void
    {
        $email = Email::make(
            from: ['email' => 'sender@example.com', 'name' => 'Sender'],
            subject: 'Test Subject',
            html: '<p>Hello</p>'
        );

        $this->assertEquals('sender@example.com', $email->from->email);
        $this->assertEquals('Sender', $email->from->name);
        $this->assertEquals('Test Subject', $email->subject);
        $this->assertEquals('<p>Hello</p>', $email->html);
    }

    public function test_creates_email_with_text_content(): void
    {
        $email = Email::make(
            from: 'sender@example.com',
            subject: 'Test',
            text: 'Plain text content'
        );

        $this->assertEquals('Plain text content', $email->text);
        $this->assertNull($email->html);
    }

    public function test_adds_single_recipient(): void
    {
        $email = Email::make('sender@example.com', 'Test')
            ->to('recipient@example.com');

        $this->assertCount(1, $email->to);
        $this->assertEquals('recipient@example.com', $email->to[0]->email);
    }

    public function test_adds_multiple_recipients(): void
    {
        $email = Email::make('sender@example.com', 'Test')
            ->to('one@example.com')
            ->to(['email' => 'two@example.com', 'name' => 'Two']);

        $this->assertCount(2, $email->to);
        $this->assertEquals('one@example.com', $email->to[0]->email);
        $this->assertEquals('two@example.com', $email->to[1]->email);
        $this->assertEquals('Two', $email->to[1]->name);
    }

    public function test_adds_recipients_as_array(): void
    {
        $email = Email::make('sender@example.com', 'Test')
            ->to([
                ['email' => 'one@example.com'],
                ['email' => 'two@example.com'],
            ]);

        $this->assertCount(2, $email->to);
    }

    public function test_adds_cc_recipients(): void
    {
        $email = Email::make('sender@example.com', 'Test')
            ->cc('cc@example.com');

        $this->assertCount(1, $email->cc);
        $this->assertEquals('cc@example.com', $email->cc[0]->email);
    }

    public function test_adds_bcc_recipients(): void
    {
        $email = Email::make('sender@example.com', 'Test')
            ->bcc('bcc@example.com');

        $this->assertCount(1, $email->bcc);
        $this->assertEquals('bcc@example.com', $email->bcc[0]->email);
    }

    public function test_adds_attachment(): void
    {
        $attachment = Attachment::fromContent('data', 'file.txt');

        $email = Email::make('sender@example.com', 'Test', html: '<p>Hi</p>')
            ->attach($attachment);

        $this->assertCount(1, $email->attachments);
        $this->assertEquals('file.txt', $email->attachments[0]->name);
    }

    public function test_adds_header(): void
    {
        $email = Email::make('sender@example.com', 'Test', html: '<p>Hi</p>')
            ->header('X-Custom', 'value');

        $this->assertEquals(['X-Custom' => 'value'], $email->headers);
    }

    public function test_adds_multiple_headers(): void
    {
        $email = Email::make('sender@example.com', 'Test', html: '<p>Hi</p>')
            ->headers([
                'X-One' => 'one',
                'X-Two' => 'two',
            ]);

        $this->assertEquals([
            'X-One' => 'one',
            'X-Two' => 'two',
        ], $email->headers);
    }

    public function test_to_array_minimal(): void
    {
        $email = Email::make('sender@example.com', 'Test', html: '<p>Hi</p>')
            ->to('recipient@example.com');

        $array = $email->toArray();

        $this->assertEquals([
            'from' => ['email' => 'sender@example.com'],
            'to' => [['email' => 'recipient@example.com']],
            'subject' => 'Test',
            'html' => '<p>Hi</p>',
        ], $array);
    }

    public function test_to_array_full(): void
    {
        $email = Email::make(
            from: ['email' => 'sender@example.com', 'name' => 'Sender'],
            subject: 'Test Subject',
            html: '<p>HTML</p>',
            text: 'Text'
        )
            ->to(['email' => 'to@example.com', 'name' => 'To'])
            ->cc('cc@example.com')
            ->bcc('bcc@example.com')
            ->header('X-Custom', 'value')
            ->attach(Attachment::fromContent('data', 'file.txt'));

        $array = $email->toArray();

        $this->assertArrayHasKey('from', $array);
        $this->assertArrayHasKey('to', $array);
        $this->assertArrayHasKey('cc', $array);
        $this->assertArrayHasKey('bcc', $array);
        $this->assertArrayHasKey('subject', $array);
        $this->assertArrayHasKey('html', $array);
        $this->assertArrayHasKey('text', $array);
        $this->assertArrayHasKey('headers', $array);
        $this->assertArrayHasKey('attachments', $array);
    }
}
