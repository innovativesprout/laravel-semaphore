<?php

namespace InnovativeSprouts\Semaphore\Facades;

use Illuminate\Support\Facades\Facade;
use InnovativeSprouts\Semaphore\Testing\SemaphoreFake;

/**
 * @method static \InnovativeSprouts\Semaphore\Semaphore retry(int $times)
 * @method static \InnovativeSprouts\Semaphore\Collections\MessageCollection send(string|array<int, string> $numbers, string $message, ?string $sender = null)
 * @method static \InnovativeSprouts\Semaphore\Collections\MessageCollection priority(string|array<int, string> $numbers, string $message, ?string $sender = null)
 * @method static \InnovativeSprouts\Semaphore\Collections\MessageCollection otp(string $number, string $message = 'Your One Time Password is {otp}.', ?string $code = null, ?string $sender = null)
 * @method static \InnovativeSprouts\Semaphore\Collections\MessageCollection messages(int $page = 1, int $limit = 100, ?string $startDate = null, ?string $endDate = null, ?string $network = null, ?string $status = null)
 * @method static \InnovativeSprouts\Semaphore\DataTransferObjects\MessageData message(int $id)
 * @method static \InnovativeSprouts\Semaphore\DataTransferObjects\AccountData account()
 * @method static \Illuminate\Support\Collection<int, \InnovativeSprouts\Semaphore\DataTransferObjects\TransactionData> transactions(int $page = 1, int $limit = 100)
 * @method static \Illuminate\Support\Collection<int, \InnovativeSprouts\Semaphore\DataTransferObjects\SenderNameData> senderNames(int $page = 1, int $limit = 100)
 * @method static \Illuminate\Support\Collection<int, \InnovativeSprouts\Semaphore\DataTransferObjects\UserData> users(int $page = 1, int $limit = 100)
 * @method static void assertSent(?callable $callback = null)
 * @method static void assertSentTo(string $number)
 * @method static void assertSentCount(int $count)
 * @method static void assertPrioritySent(?callable $callback = null)
 * @method static void assertOtpSent(?callable $callback = null)
 * @method static void assertNothingSent()
 *
 * @see \InnovativeSprouts\Semaphore\Semaphore
 * @see SemaphoreFake
 */
class Semaphore extends Facade
{
    /**
     * Replace Semaphore with a fake that records sends instead of calling the API.
     */
    public static function fake(): SemaphoreFake
    {
        static::swap($fake = new SemaphoreFake);

        return $fake;
    }

    protected static function getFacadeAccessor(): string
    {
        return \InnovativeSprouts\Semaphore\Semaphore::class;
    }
}
