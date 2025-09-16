<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Exceptions;

use RuntimeException;
use Throwable;

// @codeCoverageIgnoreStart
class OpenFeedException extends RuntimeException
{
    public function __construct(string $path, Throwable $e)
    {
        parent::__construct("Unable to open file for writing: [$path]", previous: $e);
    }
}
// @codeCoverageIgnoreEnd
