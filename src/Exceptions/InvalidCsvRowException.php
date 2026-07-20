<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Exceptions;

use UnexpectedValueException;

use function implode;

class InvalidCsvRowException extends UnexpectedValueException
{
    public function __construct(array $expected, array $actual)
    {
        parent::__construct(
            'CSV row columns do not match the established schema. Expected [' . implode(', ', $expected)
            . '], got [' . implode(', ', $actual) . '].'
        );
    }
}
