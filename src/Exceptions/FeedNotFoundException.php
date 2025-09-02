<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Exceptions;

use UnexpectedValueException;

class FeedNotFoundException extends UnexpectedValueException
{
    public function __construct(int $feedId)
    {
        parent::__construct("Feed [$feedId] not found.");
    }
}
