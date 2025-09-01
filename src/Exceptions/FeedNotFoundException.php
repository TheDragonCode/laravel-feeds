<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Exceptions;

use InvalidArgumentException;

class FeedNotFoundException extends InvalidArgumentException
{
    public function __construct(string $class)
    {
        parent::__construct("Feed [$class] not found.");
    }
}
