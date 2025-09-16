<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Exceptions;

use Exception;

// @codeCoverageIgnoreStart
class ResourceMetaException extends Exception
{
    public function __construct()
    {
        parent::__construct('Unable to get a link to the file from the resource.');
    }
}
// @codeCoverageIgnoreEnd
