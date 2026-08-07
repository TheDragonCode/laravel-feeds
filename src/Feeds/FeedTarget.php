<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Feeds;

use InvalidArgumentException;

class FeedTarget
{
    /**
     * Keep parameters small and serializable because queued jobs store this target as a snapshot.
     *
     * @param  array<string, mixed>  $parameters
     */
    public function __construct(
        public readonly string $key,
        public readonly array $parameters = [],
    ) {
        if (trim($key) === '') {
            throw new InvalidArgumentException('Feed target key cannot be empty.');
        }
    }
}
