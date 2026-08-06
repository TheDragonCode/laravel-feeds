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

    public readonly ?string $target;

    /** @param  class-string<Feed>  $feed */
    public function __construct(string $feed, Throwable $e, ?string $target = null)
    {
        parent::__construct($e->getMessage(), previous: $e);

        $this->feed   = $feed;
        $this->target = $target;
    }

    /** @return class-string<Feed> */
    public function getFeed(): string
    {
        return $this->feed;
    }

    public function getTarget(): ?string
    {
        return $this->target;
    }
}
