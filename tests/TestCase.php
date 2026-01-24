<?php

namespace ToSend\Laravel\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use ToSend\Laravel\ToSendServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            ToSendServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'ToSend' => \ToSend\Laravel\Facades\ToSend::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('tosend.api_key', 'tsend_test_api_key_12345');
        $app['config']->set('tosend.api_url', 'https://api.tosend.com');
        $app['config']->set('tosend.from', [
            'address' => 'test@example.com',
            'name' => 'Test Sender',
        ]);
    }
}
