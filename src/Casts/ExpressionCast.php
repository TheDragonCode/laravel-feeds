<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Casts;

use Cron\CronExpression;
use DragonCode\LaravelFeed\Exceptions\InvalidExpressionException;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ExpressionCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): string
    {
        return $value;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        if (! $this->isValid($value)) {
            throw new InvalidExpressionException($value);
        }

        return Str::of($value)->squish()->trim()->toString();
    }

    protected function isValid(string $value): bool
    {
        return CronExpression::isValidExpression($value);
    }
}
