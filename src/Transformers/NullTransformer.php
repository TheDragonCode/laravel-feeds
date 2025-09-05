<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Transformers;

use DragonCode\LaravelFeed\Contracts\Transformer;

class NullTransformer implements Transformer
{
    public function allow(mixed $value): bool
    {
        return $value === null;
    }

    public function transform(mixed $value): string
    {
        return 'null';
    }
}
