<?php

namespace ToSend\Laravel;

use Illuminate\Mail\MailManager;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use ToSend\Laravel\Contracts\ToSendClient;
use ToSend\Laravel\Exceptions\ToSendException;
use ToSend\Laravel\Mail\ToSendTransport;

class ToSendServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/tosend.php', 'tosend');

        $this->app->singleton(ToSendClient::class, function ($app) {
            $config = $app['config']['tosend'];

            return new ToSend(
                apiKey: $config['api_key'] ?? '',
                baseUrl: $config['api_url'] ?? 'https://api.tosend.com',
                timeout: $config['timeout'] ?? 30
            );
        });

        $this->app->alias(ToSendClient::class, 'tosend');
        $this->app->alias(ToSendClient::class, ToSend::class);

        // Resolved lazily, so an app without an admin key boots normally and
        // only fails if it actually asks for the admin client.
        $this->app->singleton(ToSendAdmin::class, function ($app) {
            $config = $app['config']['tosend'];

            if (empty($config['admin_key'])) {
                throw new ToSendException(
                    'The ToSend admin client needs an admin API key. Set TOSEND_ADMIN_KEY '
                    . '(config tosend.admin_key) to a key starting with "' . ToSendAdmin::KEY_PREFIX . '".',
                    401
                );
            }

            return new ToSendAdmin(
                apiKey: $config['admin_key'],
                baseUrl: $config['api_url'] ?? 'https://api.tosend.com',
                timeout: $config['timeout'] ?? 30
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/tosend.php' => config_path('tosend.php'),
            ], 'tosend-config');
        }

        $this->registerMailTransport();
    }

    /**
     * Register the ToSend mail transport.
     */
    protected function registerMailTransport(): void
    {
        $this->app->afterResolving(MailManager::class, function (MailManager $manager) {
            $manager->extend('tosend', function ($config) {
                $tosendConfig = $this->app['config']['tosend'];

                return new ToSendTransport(
                    client: $this->app->make(ToSendClient::class),
                    defaultFrom: $tosendConfig['from'] ?? []
                );
            });
        });
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            ToSendClient::class,
            ToSend::class,
            ToSendAdmin::class,
            'tosend',
        ];
    }
}
