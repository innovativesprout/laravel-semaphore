<?php

use Illuminate\Support\Facades\File;

it('merges default config', function () {
    expect(config('semaphore.base_url'))->toBe('https://api.semaphore.co/api/v4')
        ->and(config('semaphore.timeout'))->toBe(10)
        ->and(config('semaphore.retry.sends'))->toBe(0)
        ->and(config('semaphore.retry.reads'))->toBe(2);
});

it('ships a 500ms default retry sleep', function () {
    // TestCase overrides sleep_ms, so read the shipped file directly.
    expect((require __DIR__.'/../../config/semaphore.php')['retry']['sleep_ms'])->toBe(500);
});

it('publishes the config file', function () {
    File::delete(config_path('semaphore.php'));

    $this->artisan('vendor:publish', ['--tag' => 'semaphore-config'])->assertSuccessful();

    expect(config_path('semaphore.php'))->toBeFile();

    File::delete(config_path('semaphore.php'));
});
