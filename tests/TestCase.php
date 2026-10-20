<?php

namespace InnovativeSprouts\Semaphore\Tests;

use InnovativeSprouts\Semaphore\SemaphoreServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [SemaphoreServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('semaphore.api_key', 'test-key');
        $app['config']->set('semaphore.retry.sleep_ms', 0);
    }
}
