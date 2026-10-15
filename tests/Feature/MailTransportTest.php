<?php

namespace ToSend\Laravel\Tests\Feature;

use Illuminate\Mail\Message;
use ToSend\Laravel\Tests\TestCase;
use ToSend\Laravel\Mail\ToSendTransport;
use ToSend\Laravel\Contracts\ToSendClient;
use ToSend\Laravel\Data\EmailResponse;
use Mockery;
use Symfony\Component\Mime\Email;

class MailTransportTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_transport_sends_email(): void
    {
        $mockClient = Mockery::mock(ToSendClient::class);
        $mockClient->shouldReceive('send')
            ->once()
            ->with(Mockery::on(function ($payload) {
                return $payload['from']['email'] === 'sender@example.com'
                    && $payload['to'][0]['email'] === 'recipient@example.com'
                    && $payload['subject'] === 'Test Subject'
                    && str_contains($payload['html'], 'Hello World');
            }))
            ->andReturn(new EmailResponse('test-message-id'));

        $transport = new ToSendTransport($mockClient, []);

        $email = (new Email())
            ->from('sender@example.com')
            ->to('recipient@example.com')
            ->subject('Test Subject')
            ->html('<p>Hello World</p>');

        $sentMessage = $transport->send($email);

        $this->assertNotNull($sentMessage);
    }

    public function test_transport_uses_default_from(): void
    {
        $mockClient = Mockery::mock(ToSendClient::class);
        $mockClient->shouldReceive('send')
            ->once()
            ->with(Mockery::on(function ($payload) {
                return $payload['from']['email'] === 'default@example.com'
                    && $payload['from']['name'] === 'Default Sender';
            }))
            ->andReturn(new EmailResponse('test-message-id'));

        $transport = new ToSendTransport($mockClient, [
            'address' => 'default@example.com',
            'name' => 'Default Sender',
        ]);

        $email = (new Email())
            ->to('recipient@example.com')
            ->subject('Test')
            ->html('<p>Hi</p>');

        $sentMessage = $transport->send($email);

        $this->assertNotNull($sentMessage);
    }

    public function test_transport_includes_cc_and_bcc(): void
    {
        $mockClient = Mockery::mock(ToSendClient::class);
        $mockClient->shouldReceive('send')
            ->once()
            ->with(Mockery::on(function ($payload) {
                return count($payload['cc']) === 1
                    && $payload['cc'][0]['email'] === 'cc@example.com'
                    && count($payload['bcc']) === 1
                    && $payload['bcc'][0]['email'] === 'bcc@example.com';
            }))
            ->andReturn(new EmailResponse('test-message-id'));

        $transport = new ToSendTransport($mockClient, []);

        $email = (new Email())
            ->from('sender@example.com')
            ->to('recipient@example.com')
            ->cc('cc@example.com')
            ->bcc('bcc@example.com')
            ->subject('Test')
            ->html('<p>Hi</p>');

        $sentMessage = $transport->send($email);

        $this->assertNotNull($sentMessage);
    }

    public function test_transport_includes_reply_to(): void
    {
        $mockClient = Mockery::mock(ToSendClient::class);
        $mockClient->shouldReceive('send')
            ->once()
            ->with(Mockery::on(function ($payload) {
                return isset($payload['reply_to'])
                    && $payload['reply_to']['email'] === 'reply@example.com';
            }))
            ->andReturn(new EmailResponse('test-message-id'));

        $transport = new ToSendTransport($mockClient, []);

        $email = (new Email())
            ->from('sender@example.com')
            ->to('recipient@example.com')
            ->replyTo('reply@example.com')
            ->subject('Test')
            ->html('<p>Hi</p>');

        $sentMessage = $transport->send($email);

        $this->assertNotNull($sentMessage);
    }

    public function test_transport_includes_text_content(): void
    {
        $mockClient = Mockery::mock(ToSendClient::class);
        $mockClient->shouldReceive('send')
            ->once()
            ->with(Mockery::on(function ($payload) {
                return $payload['text'] === 'Plain text content';
            }))
            ->andReturn(new EmailResponse('test-message-id'));

        $transport = new ToSendTransport($mockClient, []);

        $email = (new Email())
            ->from('sender@example.com')
            ->to('recipient@example.com')
            ->subject('Test')
            ->text('Plain text content');

        $sentMessage = $transport->send($email);

        $this->assertNotNull($sentMessage);
    }

    public function test_transport_string_representation(): void
    {
        $mockClient = Mockery::mock(ToSendClient::class);
        $transport = new ToSendTransport($mockClient, []);

        $this->assertEquals('tosend', (string) $transport);
    }

    /** Capture the payload the transport hands to the client. */
    private function sendAndCapture(Email $email): array
    {
        $captured = null;
        $mockClient = Mockery::mock(ToSendClient::class);
        $mockClient->shouldReceive('send')->once()->with(Mockery::on(function ($payload) use (&$captured) {
            $captured = $payload;
            return true;
        }))->andReturn(new EmailResponse('test-message-id'));

        (new ToSendTransport($mockClient, []))->send($email);

        return $captured;
    }

    public function test_laravel_embed_data_is_sent_inline_with_its_content_id(): void
    {
        $email = (new Email())->from('sender@example.com')->to('recipient@example.com')->subject('Inline');
        $cid = (new Message($email))->embedData('png-bytes', 'logo.png', 'image/png');
        $email->html('<p><img src="' . $cid . '"></p>');

        $payload = $this->sendAndCapture($email);

        $contentId = substr($cid, 4);
        $this->assertSame([[
            'name' => 'logo.png',
            'type' => 'image/png',
            'content' => base64_encode('png-bytes'),
            'content_id' => $contentId,
        ]], $payload['attachments']);
        $this->assertSame('<p><img src="cid:' . $contentId . '"></p>', $payload['html']);
    }

    public function test_symfony_embed_by_name_is_rewritten_to_the_content_id(): void
    {
        $email = (new Email())->from('sender@example.com')->to('recipient@example.com')->subject('Inline')
            ->embed('png-bytes', 'logo', 'image/png')
            ->html('<p><img src="cid:logo"></p>');

        $payload = $this->sendAndCapture($email);

        $contentId = $payload['attachments'][0]['content_id'] ?? null;
        $this->assertNotNull($contentId);
        $this->assertNotSame('logo', $contentId);
        $this->assertSame('<p><img src="cid:' . $contentId . '"></p>', $payload['html']);
    }

    public function test_regular_attachment_has_no_content_id(): void
    {
        $email = (new Email())->from('sender@example.com')->to('recipient@example.com')->subject('Invoice')
            ->attach('pdf-bytes', 'invoice.pdf', 'application/pdf')
            ->html('<p>Invoice attached</p>');

        $payload = $this->sendAndCapture($email);

        $this->assertSame([[
            'name' => 'invoice.pdf',
            'type' => 'application/pdf',
            'content' => base64_encode('pdf-bytes'),
        ]], $payload['attachments']);
        $this->assertSame('<p>Invoice attached</p>', $payload['html']);
    }
}
