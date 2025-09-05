<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Transformers;

use BackedEnum;
use DragonCode\LaravelFeed\Contracts\Transformer;
use UnitEnum;

class EnumTransformer implements Transformer
{
    public function allow(mixed $value): bool
    {
        return $value instanceof UnitEnum;
    }

    /**
     * @param  UnitEnum|BackedEnum  $value
     */
    public function transform(mixed $value): string
    {
        return (string) ($value?->value ?? $value->name);
    }
}
