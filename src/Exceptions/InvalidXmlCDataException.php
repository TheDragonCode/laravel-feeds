<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Exceptions;

use Throwable;
use UnexpectedValueException;

class InvalidXmlCDataException extends UnexpectedValueException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct(
            'Unable to create an XML CDATA section for the [@cdata] directive.',
            previous: $previous
        );
    }
}
