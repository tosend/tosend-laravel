<?php

namespace ToSend\Laravel\Tests\Feature;

use ToSend\Laravel\Tests\TestCase;
use ToSend\Laravel\ToSend;
use ToSend\Laravel\ToSendAdmin;
use ToSend\Laravel\Exceptions\ToSendException;
use ToSend\Laravel\Contracts\ToSendClient;
use ToSend\Laravel\Facades\ToSend as ToSendFacade;

class ServiceProviderTest extends TestCase
{
    public function test_tosend_client_is_bound(): void
    {
        $client = $this->app->make(ToSendClient::class);

        $this->assertInstanceOf(ToSend::class, $client);
    }

    public function test_tosend_is_singleton(): void
    {
        $client1 = $this->app->make(ToSendClient::class);
        $client2 = $this->app->make(ToSendClient::class);

        $this->assertSame($client1, $client2);
    }

    public function test_facade_resolves_to_client(): void
    {
        $client = ToSendFacade::getFacadeRoot();

        $this->assertInstanceOf(ToSend::class, $client);
    }

    public function test_config_is_loaded(): void
    {
        $this->assertEquals('tsend_test_api_key_12345', config('tosend.api_key'));
        $this->assertEquals('https://api.tosend.com', config('tosend.api_url'));
        $this->assertEquals('test@example.com', config('tosend.from.address'));
        $this->assertEquals('Test Sender', config('tosend.from.name'));
    }

    public function test_admin_client_resolves_when_a_key_is_configured(): void
    {
        config(['tosend.admin_key' => 'tsend_admin_0123456789abcdef0123456789abcdef']);

        $this->assertInstanceOf(ToSendAdmin::class, $this->app->make(ToSendAdmin::class));
    }

    public function test_admin_client_without_a_key_fails_clearly_only_when_resolved(): void
    {
        config(['tosend.admin_key' => null]);

        // The app booted and the sending client still works.
        $this->assertInstanceOf(ToSend::class, $this->app->make(ToSendClient::class));

        $this->expectException(ToSendException::class);
        $this->expectExceptionMessage('TOSEND_ADMIN_KEY');

        $this->app->make(ToSendAdmin::class);
    }

    public function test_mail_transport_is_registered(): void
    {
        config(['mail.mailers.tosend' => ['transport' => 'tosend']]);

        $mailer = app('mail.manager')->mailer('tosend');

        $this->assertNotNull($mailer);
    }
}
