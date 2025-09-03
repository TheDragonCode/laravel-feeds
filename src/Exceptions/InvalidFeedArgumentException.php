<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Exceptions;

use Exception;

use function gettype;

class InvalidFeedArgumentException extends Exception
{
    public function __construct(mixed $id)
    {
        $type = gettype($id);

        parent::__construct("Feed ID must be of type integer, [$type] given.");
    }
}
