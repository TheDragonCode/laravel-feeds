<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsInboundAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class SlugCast implements CastsInboundAttributes
{
    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        return Str::slug($value);
    }
}
