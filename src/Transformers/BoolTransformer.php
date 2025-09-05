<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Transformers;

use DragonCode\LaravelFeed\Contracts\Transformer;

use function is_bool;

class BoolTransformer implements Transformer
{
    public function allow(mixed $value): bool
    {
        return is_bool($value);
    }

    public function transform(mixed $value): string
    {
        return $value ? 'true' : 'false';
    }
}
