<?php

namespace InnovativeSprouts\Semaphore;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\ServiceProvider;
use InnovativeSprouts\Semaphore\Http\SemaphoreConnector;
use InnovativeSprouts\Semaphore\Notifications\SemaphoreChannel;

class SemaphoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/semaphore.php', 'semaphore');

        // Config is read when first resolved, so a missing key never breaks boot.
        $this->app->singleton(SemaphoreConnector::class, function (Application $app) {
            $config = $app->make('config');

            return new SemaphoreConnector(
                apiKey: $config->get('semaphore.api_key'),
                defaultSender: $config->get('semaphore.sender_name'),
                baseUrl: (string) $config->get('semaphore.base_url'),
                timeout: (int) $config->get('semaphore.timeout'),
                sendRetries: (int) $config->get('semaphore.retry.sends'),
                readRetries: (int) $config->get('semaphore.retry.reads'),
                sleepMs: (int) $config->get('semaphore.retry.sleep_ms'),
            );
        });

        $this->app->scoped(Semaphore::class);
    }

    public function boot(): void
    {
        $this->callAfterResolving(ChannelManager::class, function (ChannelManager $channels) {
            $channels->extend('semaphore', fn (Application $app) => $app->make(SemaphoreChannel::class));
        });

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/semaphore.php' => config_path('semaphore.php'),
            ], 'semaphore-config');
        }
    }
}
