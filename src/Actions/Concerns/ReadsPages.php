<?php

namespace InnovativeSprouts\Semaphore\Actions\Concerns;

use Illuminate\Support\Collection;
use InnovativeSprouts\Semaphore\Exceptions\ValidationException;
use InvalidArgumentException;

trait ReadsPages
{
    /**
     * @template T
     *
     * @param  callable(array<string, mixed>): T  $map
     * @return Collection<int, T>
     */
    private function page(string $path, int $page, int $limit, callable $map): Collection
    {
        if ($page < 1 || $limit < 1 || $limit > 1000) {
            throw new InvalidArgumentException('Page must be 1 or greater and limit between 1 and 1000.');
        }

        $items = $this->connector->get($path, ['page' => $page, 'limit' => $limit]);

        // These endpoints return lists; an object here is an error sent with HTTP 200.
        if (! array_is_list($items)) {
            throw ValidationException::fromBody($items);
        }

        return collect($items)
            ->filter(fn (mixed $item) => is_array($item))
            ->map(fn (array $item) => $map($item))
            ->values();
    }
}
