<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Exceptions;

use UnexpectedValueException;

class InvalidCsvValueException extends UnexpectedValueException
{
    public function __construct(int|string $column)
    {
        parent::__construct("CSV column [$column] contains a nested value. Nested arrays are not supported.");
    }
}
