<?php

use Illuminate\Http\Client\Request;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use InnovativeSprouts\Semaphore\Exceptions\RateLimitException;
use InnovativeSprouts\Semaphore\Notifications\SemaphoreChannel;
use InnovativeSprouts\Semaphore\Notifications\SemaphoreMessage;

beforeEach(fn () => Http::preventStrayRequests());

class SmsUser
{
    use Notifiable;

    public function __construct(public ?string $phone = '09171234567') {}

    public function routeNotificationForSemaphore(): ?string
    {
        return $this->phone;
    }
}

class SmsNotification extends Notification
{
    /** @param string|array<int, string> $channels */
    public function __construct(private SemaphoreMessage|string $message, private string|array $channels = 'semaphore') {}

    public function via(object $notifiable): array
    {
        return (array) $this->channels;
    }

    public function toSemaphore(object $notifiable): SemaphoreMessage|string
    {
        return $this->message;
    }
}

it('sends through the semaphore channel name', function () {
    Http::fake(['*' => Http::response(apiFixture('messages'))]);

    (new SmsUser)->notify(new SmsNotification(SemaphoreMessage::create('Order shipped')));

    Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/messages')
        && $r['number'] === '09171234567' && $r['message'] === 'Order shipped');
});

it('sends through the channel class name', function () {
    Http::fake(['*' => Http::response(apiFixture('messages'))]);

    (new SmsUser)->notify(new SmsNotification('Hi', [SemaphoreChannel::class]));

    Http::assertSentCount(1);
});

it('accepts a plain string message', function () {
    Http::fake(['*' => Http::response(apiFixture('messages'))]);

    (new SmsUser)->notify(new SmsNotification('Plain text'));

    Http::assertSent(fn (Request $r) => $r['message'] === 'Plain text');
});

it('sends priority messages', function () {
    Http::fake(['*' => Http::response(apiFixture('messages'))]);

    (new SmsUser)->notify(new SmsNotification(SemaphoreMessage::create('Down!')->priority()));

    Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/priority'));
});

it('sends OTPs with a custom code and sender', function () {
    Http::fake(['*' => Http::response(apiFixture('otp'))]);

    (new SmsUser)->notify(new SmsNotification(SemaphoreMessage::create('Code {otp}')->code('111111')->sender('SHOP')));

    Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/otp')
        && $r['code'] === '111111' && $r['sendername'] === 'SHOP');
});

it('lets the last of priority() and otp() win', function () {
    $message = SemaphoreMessage::create('x')->otp()->priority();

    expect($message->isPriority())->toBeTrue()->and($message->isOtp())->toBeFalse();
});

it('sends to on-demand routes', function () {
    Http::fake(['*' => Http::response(apiFixture('messages'))]);

    Illuminate\Support\Facades\Notification::route('semaphore', '09181234567')->notify(new SmsNotification('Hi'));

    Http::assertSent(fn (Request $r) => $r['number'] === '09181234567');
});

it('skips notifiables without a route', function () {
    (new SmsUser(phone: null))->notify(new SmsNotification('Hi'));

    Http::assertNothingSent();
});

it('lets rate limits propagate for queue backoff', function () {
    Http::fake(['*' => Http::response([], 429, ['Retry-After' => '30'])]);

    (new SmsUser)->notify(new SmsNotification('Hi'));
})->throws(RateLimitException::class);

it('refuses to send an OTP to several numbers', function () {
    Illuminate\Support\Facades\Notification::route('semaphore', ['09171234567', '09181234567'])
        ->notify(new SmsNotification(SemaphoreMessage::create('Code {otp}')->otp()));
})->throws(InvalidArgumentException::class, 'exactly one number');
