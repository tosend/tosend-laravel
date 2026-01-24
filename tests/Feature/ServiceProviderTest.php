<?php

namespace ToSend\Laravel\Tests\Feature;

use ToSend\Laravel\Tests\TestCase;
use ToSend\Laravel\ToSend;
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

    public function test_mail_transport_is_registered(): void
    {
        config(['mail.mailers.tosend' => ['transport' => 'tosend']]);

        $mailer = app('mail.manager')->mailer('tosend');

        $this->assertNotNull($mailer);
    }
}
