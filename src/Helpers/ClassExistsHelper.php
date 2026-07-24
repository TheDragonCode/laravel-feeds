<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Helpers;

use function class_exists;

class ClassExistsHelper
{
    public function exists(string $class): bool
    {
        return class_exists($class);
    }
}
