<?php

namespace InnovativeSprouts\Semaphore\Notifications;

use Illuminate\Contracts\Container\Container;
use Illuminate\Notifications\Notification;
use InnovativeSprouts\Semaphore\Collections\MessageCollection;
use InnovativeSprouts\Semaphore\Semaphore;
use InvalidArgumentException;
use LogicException;

class SemaphoreChannel
{
    public function __construct(private readonly Container $container) {}

    public function send(object $notifiable, Notification $notification): ?MessageCollection
    {
        $route = method_exists($notifiable, 'routeNotificationFor')
            ? $notifiable->routeNotificationFor('semaphore', $notification)
            : null;

        if ($route === null || $route === '' || $route === []) {
            return null;
        }

        if (! method_exists($notification, 'toSemaphore')) {
            throw new LogicException($notification::class.' must define toSemaphore() to use the semaphore channel.');
        }

        $message = $notification->toSemaphore($notifiable);
        $message = is_string($message) ? SemaphoreMessage::create($message) : $message;

        if (! $message instanceof SemaphoreMessage) {
            throw new LogicException($notification::class.'::toSemaphore() must return a string or SemaphoreMessage.');
        }

        // Resolved per send so Semaphore::fake() also captures notifications.
        $semaphore = $this->container->make(Semaphore::class);

        if ($message->isOtp()) {
            return $semaphore->otp(self::singleNumber($route), $message->content, $message->getCode(), $message->getSender());
        }

        return $message->isPriority()
            ? $semaphore->priority($route, $message->content, $message->getSender())
            : $semaphore->send($route, $message->content, $message->getSender());
    }

    /** @param string|array<int, string> $route */
    private static function singleNumber(string|array $route): string
    {
        if (is_string($route)) {
            return $route;
        }

        if (count($route) !== 1) {
            throw new InvalidArgumentException('An OTP notification must be routed to exactly one number.');
        }

        return (string) reset($route);
    }
}
