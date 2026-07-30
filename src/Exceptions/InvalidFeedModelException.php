<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Exceptions;

use DragonCode\LaravelFeed\Models\Feed;
use UnexpectedValueException;

use function get_debug_type;
use function is_string;
use function sprintf;

class InvalidFeedModelException extends UnexpectedValueException
{
    public function __construct(mixed $model)
    {
        parent::__construct(
            sprintf(
                'The [feeds.model] configuration value [%s] must be a class extending %s.',
                is_string($model) ? $model : get_debug_type($model),
                Feed::class
            )
        );
    }
}
