<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Transformers;

use DateTimeInterface;
use DragonCode\LaravelFeed\Contracts\Transformer;
use Illuminate\Support\Carbon;

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
        return $this->resolve($value)
            ->when($this->timezone(), fn (Carbon $date, string $zone) => $date->setTimezone($zone))
            ->format($this->format());
    }

    protected function resolve(mixed $date): Carbon
    {
        return Carbon::parse($date);
    }

    protected function format(): string
    {
        return config('feeds.date.format');
    }

    protected function timezone(): string
    {
        return config('feeds.date.timezone');
    }
}
