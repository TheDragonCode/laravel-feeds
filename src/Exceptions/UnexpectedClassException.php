<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Exceptions;

use UnexpectedValueException;

class UnexpectedClassException extends UnexpectedValueException
{
    public function __construct(string $class)
    {
        parent::__construct("Class [$class] does not exist.");
    }
}
