<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Exceptions;

use RuntimeException;

class WriteFeedException extends RuntimeException
{
    public function __construct(string $path)
    {
        parent::__construct("Failed to write to the feed: [$path].");
    }
}
