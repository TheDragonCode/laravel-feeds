<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Exceptions;

use DragonCode\LaravelFeed\Feeds\Feed;
use RuntimeException;
use Throwable;

class FeedGenerationException extends RuntimeException
{
    /** @var class-string<Feed> */
    public readonly string $feed;

    /**
     * @param  class-string<Feed>  $feed
     */
    public function __construct(string $feed, Throwable $e)
    {
        parent::__construct($e->getMessage(), previous: $e);

        $this->feed = $feed;
    }

    /**
     * @return class-string<Feed>
     */
    public function getFeed(): string
    {
        return $this->feed;
    }
}
