<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Transformers;

use DateTimeInterface;
use DragonCode\LaravelFeed\Contracts\Transformer;

use function config;

class DateTimeTransformer implements Transformer
{
    public function allow(mixed $value): bool
    {
        return $value instanceof DateTimeInterface;
    }

    /**
     * @param  DateTimeInterface  $value
     */
    public function transform(mixed $value): string
    {
        return $value->format(
            $this->format()
        );
    }

    protected function format(): string
    {
        return config('feeds.formats.date');
    }
}
