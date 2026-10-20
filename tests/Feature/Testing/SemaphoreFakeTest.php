<?php

use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use InnovativeSprouts\Semaphore\DataTransferObjects\MessageData;
use InnovativeSprouts\Semaphore\Enums\MessageStatus;
use InnovativeSprouts\Semaphore\Facades\Semaphore;
use InnovativeSprouts\Semaphore\Notifications\SemaphoreMessage;
use InnovativeSprouts\Semaphore\Testing\SemaphoreFake;
use PHPUnit\Framework\ExpectationFailedException;

beforeEach(fn () => Http::preventStrayRequests());

it('records sends without HTTP', function () {
    $fake = Semaphore::fake();

    $sent = Semaphore::send(['09171234567', '09181234567'], 'Hi');

    expect($fake)->toBeInstanceOf(SemaphoreFake::class)
        ->and($sent)->toHaveCount(2)
        ->and($sent->first()->status)->toBe(MessageStatus::Queued)
        ->and($sent->pluck('id')->all())->toBe([1, 2]);

    Semaphore::assertSentCount(2);
    Semaphore::assertSentTo('09181234567');
    Semaphore::assertSent(fn (MessageData $m) => $m->message === 'Hi');
    Http::assertNothingSent();
});

it('matches numbers regardless of formatting', function () {
    Semaphore::fake();

    Semaphore::send('0917-123-4567', 'Hi');

    Semaphore::assertSentTo('0917 123 4567');
});

it('generates a six-digit OTP code and fills the placeholder', function () {
    Semaphore::fake();

    $message = Semaphore::otp('09171234567', 'Code {otp}')->first();

    expect($message->code)->toMatch('/^\d{6}$/')
        ->and($message->message)->toBe("Code {$message->code}");
    Semaphore::assertOtpSent(fn (MessageData $m) => $m->recipient === '09171234567');
});

it('keeps a custom OTP code', function () {
    Semaphore::fake();

    expect(Semaphore::otp('09171234567', 'Code {otp}', code: '123456')->first()->code)->toBe('123456');
});

it('tracks priority sends separately', function () {
    Semaphore::fake();

    Semaphore::priority('09171234567', 'Down!');

    Semaphore::assertPrioritySent();
    expect(fn () => Semaphore::assertOtpSent())->toThrow(ExpectationFailedException::class);
});

it('fails assertNothingSent after a send', function () {
    Semaphore::fake();
    Semaphore::assertNothingSent();

    Semaphore::send('09171234567', 'Hi');

    expect(fn () => Semaphore::assertNothingSent())->toThrow(ExpectationFailedException::class);
});

it('fails assertSentTo for an unsent number', function () {
    Semaphore::fake();
    Semaphore::send('09171234567', 'Hi');

    expect(fn () => Semaphore::assertSentTo('09181234567'))->toThrow(ExpectationFailedException::class);
});

it('still validates input', function () {
    Semaphore::fake();

    expect(fn () => Semaphore::send([], 'x'))->toThrow(InvalidArgumentException::class)
        ->and(fn () => Semaphore::send('09171234567', 'TEST message'))->toThrow(InvalidArgumentException::class);
});

it('captures notifications', function () {
    Semaphore::fake();

    $user = new class
    {
        use Notifiable;

        public function routeNotificationForSemaphore(): string
        {
            return '09171234567';
        }
    };

    $user->notify(new class extends Notification
    {
        public function via(object $notifiable): array
        {
            return ['semaphore'];
        }

        public function toSemaphore(object $notifiable): SemaphoreMessage
        {
            return SemaphoreMessage::create('Code {otp}')->otp();
        }
    });

    Semaphore::assertOtpSent();
    Http::assertNothingSent();
});

it('returns harmless read results', function () {
    Semaphore::fake();
    $sent = Semaphore::send('09171234567', 'Hi')->first();

    expect(Semaphore::account()->creditBalance)->toBe(0)
        ->and(Semaphore::messages())->toHaveCount(1)
        ->and(Semaphore::message($sent->id)->recipient)->toBe('09171234567')
        ->and(Semaphore::transactions())->toBeEmpty()
        ->and(Semaphore::senderNames())->toBeEmpty()
        ->and(Semaphore::users())->toBeEmpty();
});

it('filters faked messages by status, network and date', function () {
    Semaphore::fake();
    Semaphore::send('09171234567', 'Hi');
    $today = now()->toDateString();

    expect(Semaphore::messages(status: 'Queued'))->toHaveCount(1)
        ->and(Semaphore::messages(status: 'sent'))->toBeEmpty()
        ->and(Semaphore::messages(network: 'globe'))->toBeEmpty()
        ->and(Semaphore::messages(startDate: $today, endDate: $today))->toHaveCount(1)
        ->and(Semaphore::messages(startDate: now()->addDay()->toDateString()))->toBeEmpty()
        ->and(Semaphore::messages(endDate: now()->subDay()->toDateString()))->toBeEmpty();
});

it('falls back to the configured sender name', function () {
    config(['semaphore.sender_name' => 'SHOP']);
    Semaphore::fake();

    expect(Semaphore::send('09171234567', 'Hi')->first()->senderName)->toBe('SHOP')
        ->and(Semaphore::send('09171234567', 'Hi', 'OTHER')->first()->senderName)->toBe('OTHER');
});
