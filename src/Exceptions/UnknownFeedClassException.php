<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Exceptions;

use DragonCode\LaravelFeed\Feeds\Feed;
use UnexpectedValueException;

use function sprintf;

class UnknownFeedClassException extends UnexpectedValueException
{
    public function __construct(string $class)
    {

        parent::__construct(
            sprintf('The [%s] class must extend from the %s class.', $class, Feed::class)
        );
    }
}
