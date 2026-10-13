<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Exceptions;

use Exception;

use function gettype;

class InvalidFeedArgumentException extends Exception
{
    public function __construct(mixed $slug)
    {
        $type = gettype($slug);

        parent::__construct("Feed slug must be a non-empty normalized string, [$type] given.");
    }
}
