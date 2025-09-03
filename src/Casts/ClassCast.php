<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Casts;

use DragonCode\LaravelFeed\Exceptions\UnexpectedClassException;
use DragonCode\LaravelFeed\Exceptions\UnknownFeedClassException;
use DragonCode\LaravelFeed\Feeds\Feed;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

use function class_exists;
use function is_a;

class ClassCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): string
    {
        return $value;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        if (! class_exists($value)) {
            throw new UnexpectedClassException($value);
        }

        if (! is_a($value, Feed::class, true)) {
            throw new UnknownFeedClassException($value);
        }

        return $value;
    }
}
