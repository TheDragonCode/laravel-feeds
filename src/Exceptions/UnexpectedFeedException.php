<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Exceptions;

use DragonCode\LaravelFeed\Feeds\Feed;
use UnexpectedValueException;

class UnexpectedFeedException extends UnexpectedValueException
{
    public function __construct(string $class)
    {
        parent::__construct(
            sprintf('The [%s] class must implement %s.', $class, Feed::class)
        );
    }
}
