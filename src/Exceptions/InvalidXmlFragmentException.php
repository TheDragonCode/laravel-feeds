<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Exceptions;

use Throwable;
use UnexpectedValueException;

class InvalidXmlFragmentException extends UnexpectedValueException
{
    public function __construct(?string $reason = null, ?Throwable $previous = null)
    {
        $message = 'Invalid XML fragment supplied to the [@mixed] directive.';

        if ($reason) {
            $message .= ' ' . $reason;
        }

        parent::__construct($message, previous: $previous);
    }
}
