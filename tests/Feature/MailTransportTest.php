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
}
