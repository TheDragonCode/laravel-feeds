<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Exceptions;

use InvalidArgumentException;

class InvalidExpressionException extends InvalidArgumentException
{
    public function __construct(string $expression)
    {
        parent::__construct("[$expression] is not a valid CRON expression");
    }
}
