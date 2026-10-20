# Laravel Semaphore

A modern Laravel client and notification channel for the [Semaphore](https://semaphore.co/docs) SMS API (Philippines).

- Regular, bulk, priority and OTP sending
- Bulk sends over 1,000 numbers are split automatically
- A Laravel notification channel
- Typed responses (DTOs and enums) and a clear exception for each failure
- Segment and credit counter, so you know what a message costs before sending
- `Semaphore::fake()` for tests, with no HTTP calls

Requires PHP 8.2+ and Laravel 12 or 13.

## Installation

```bash
composer require innovativesprouts/laravel-semaphore
```

Add your key to `.env`:

```dotenv
SEMAPHORE_API_KEY=your-api-key
SEMAPHORE_SENDER_NAME=MYSHOP   # optional; defaults to your account's default sender name
```

Optionally publish the config:

```bash
php artisan vendor:publish --tag=semaphore-config
```

## Sending

```php
use InnovativeSprouts\Semaphore\Facades\Semaphore;

Semaphore::send('09171234567', 'Your order has shipped.');

// Many numbers (array or comma-separated). Over 1,000 are sent in chunks.
Semaphore::send(['09171234567', '09181234567'], 'Sale today!', sender: 'MYSHOP');

// Priority queue: skips Semaphore's normal queue (2 credits per segment).
Semaphore::priority('09171234567', 'Server is down.');

// OTP route: "{otp}" is replaced with a generated code (2 credits per segment).
$code = Semaphore::otp('09171234567', 'Your code is {otp}.')->first()->code;

// Or use your own code.
Semaphore::otp('09171234567', 'Your code is {otp}.', code: '482913');
```

Every send returns a `MessageCollection` of `MessageData` objects with `id`, `recipient`, `message`, `code`, `senderName`, `network` (`Network` enum), `status` (`MessageStatus` enum), `type`, `createdAt` and more. Unknown values from the API are kept on `rawStatus`, `rawType` and `rawNetwork`.

Numbers may be `09XXXXXXXXX`, `639XXXXXXXXX` or `+639XXXXXXXXX`. Spaces and dashes are removed. Invalid numbers, empty messages, and messages starting with the word "TEST" (which Semaphore silently drops) throw an `InvalidArgumentException` before any request is made.

Sending is asynchronous: a `Failed` status is not an exception. Check a message later with `Semaphore::message($id)`.

## Reading

```php
Semaphore::messages(status: 'sent', startDate: '2026-10-01', endDate: '2026-10-31', page: 2, limit: 100);
Semaphore::message(12345);
Semaphore::account()->creditBalance;
Semaphore::transactions();
Semaphore::senderNames();
Semaphore::users();
```

Dates must be `Y-m-d`; `limit` is 1 to 1000.

## Notifications

```php
use Illuminate\Notifications\Notification;
use InnovativeSprouts\Semaphore\Notifications\SemaphoreMessage;

class OrderShipped extends Notification
{
    public function via(object $notifiable): array
    {
        return ['semaphore'];
    }

    public function toSemaphore(object $notifiable): SemaphoreMessage
    {
        return SemaphoreMessage::create('Your order has shipped.');
        // ->priority()   use the priority queue
        // ->otp()        use the OTP route ("{otp}" is replaced)
        // ->code('1234') OTP with your own code
        // ->sender('X')  override the sender name
    }
}
```

`toSemaphore()` can also return a plain string. Tell Laravel where to send:

```php
class User extends Authenticatable
{
    use Notifiable;

    public function routeNotificationForSemaphore(): string
    {
        return $this->phone;
    }
}
```

Notifiables without a number are skipped. OTP notifications must go to exactly one number.

## Errors

Every failure extends `InnovativeSprouts\Semaphore\Exceptions\SemaphoreException`:

| Exception | When |
|---|---|
| `AuthenticationException` | Bad or missing API key (401/403) |
| `ValidationException` | Semaphore rejected the input; `$e->errors()` has its messages |
| `RateLimitException` | Too many requests (429); `$e->retryAfter` is in seconds |
| `ServerException` | 5xx, timeouts, connection failures, unreadable responses |
| `MissingApiKeyException` | `SEMAPHORE_API_KEY` is empty when a request is made |

If a bulk send fails partway, `$e->sentMessages` holds the messages Semaphore already accepted.

## Retries and rate limits

Read endpoints retry twice on connection errors and 5xx. **Sends are not retried by default.** If a send times out after Semaphore already accepted it, a retry sends a **duplicate SMS and charges you twice**. Opt in only if that trade-off is right for you:

```dotenv
SEMAPHORE_RETRY_SENDS=2
```

```php
Semaphore::retry(3)->send('09171234567', 'Critical alert'); // this call only
```

4xx and 429 responses are never retried. Rate limits throw `RateLimitException` straight away. For queued notifications, let Laravel retry the job later:

```php
class OrderShipped extends Notification implements ShouldQueue
{
    use Queueable;

    public $tries = 5;

    public function backoff(): array
    {
        return [30, 60, 120];
    }
}
```

## Segments and credits

Semaphore charges per 160-character segment. Check the cost before sending:

```php
use InnovativeSprouts\Semaphore\Enums\MessageType;
use InnovativeSprouts\Semaphore\ValueObjects\MessageBody;
use InnovativeSprouts\Semaphore\ValueObjects\Recipients;

$body = MessageBody::from('Your order #123 has shipped.');

$body->encoding();                   // Encoding::Gsm7 or Encoding::Unicode (emoji, many accents)
$body->segments();                   // GSM-7: 160 / 153 per part. Unicode: 70 / 67 per part.
$body->credits(MessageType::Single); // 1 credit per segment; Priority and Otp cost 2

Recipients::from($numbers)->credits($body, MessageType::Single); // total for all recipients
```

## Testing

```php
use InnovativeSprouts\Semaphore\DataTransferObjects\MessageData;
use InnovativeSprouts\Semaphore\Facades\Semaphore;

Semaphore::fake();

// ... run your code ...

Semaphore::assertSentTo('09171234567');
Semaphore::assertSent(fn (MessageData $m) => str_contains($m->message, 'shipped'));
Semaphore::assertSentCount(1);
Semaphore::assertOtpSent();
Semaphore::assertPrioritySent();
Semaphore::assertNothingSent();
```

The fake validates input like production, captures notifications too, and returns realistic `MessageData` (OTP sends get a 6-digit `code`, and the sender falls back to `SEMAPHORE_SENDER_NAME`). `Semaphore::messages()` returns what was sent, filtered by `status` and by `startDate`/`endDate` against the send time; faked messages have no network, so any `network` filter returns nothing.

## Architecture

The package follows Martin Joo's domain-driven Laravel style: single-purpose `Actions` (injectable, e.g. `SendMessageAction`), value objects that validate input, readonly DTOs, enums, and one `SemaphoreConnector` that alone talks HTTP.

## Coming later

- OTP verification (store, expire and check codes)

## License

MIT. See [LICENSE.md](LICENSE.md).
