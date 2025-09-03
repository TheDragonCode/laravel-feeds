<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Exceptions;

use RuntimeException;

class OpenFeedException extends RuntimeException
{
    public function __construct(string $path)
    {
        parent::__construct("Unable to open file for writing: [$path]");
    }
}
