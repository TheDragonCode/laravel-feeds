<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Concerns;

use DragonCode\LaravelFeed\Data\OptionalData;
use Spatie\LaravelData\Optional;

use function class_exists;

trait InteractsWithOptional
{
    protected function isOptional(mixed $value): bool
    {
        if ($value instanceof OptionalData) {
            return true;
        }

        return class_exists(Optional::class) && $value instanceof Optional;
    }
}
