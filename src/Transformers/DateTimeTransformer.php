<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Transformers;

use DateTimeInterface;
use DragonCode\LaravelFeed\Contracts\Transformer;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Carbon;

use function config;

class DateTimeTransformer implements Transformer
{
    protected ?string $dateFormat = null;

    protected ?Repository $configuration = null;

    protected bool $transforming = false;

    public function __construct(?string $dateFormat = null)
    {
        $this->dateFormat = $dateFormat;
    }

    public function allow(mixed $value): bool
    {
        return $value instanceof DateTimeInterface;
    }

    /** @param  DateTimeInterface  $value */
    public function transform(mixed $value): string
    {
        $previousConfiguration = $this->configuration;
        $previousTransforming  = $this->transforming;

        $this->configuration = null;
        $this->transforming  = true;

        try {
            return $this->resolve($value)
                ->when($this->timezone(), fn (Carbon $date, string $zone) => $date->setTimezone($zone))
                ->format($this->format());
        } finally {
            $this->configuration = $previousConfiguration;
            $this->transforming  = $previousTransforming;
        }
    }

    protected function resolve(mixed $date): Carbon
    {
        return Carbon::parse($date);
    }

    protected function format(): string
    {
        return $this->dateFormat ?? ($this->configuration ?? $this->configuration())->get('feeds.date.format');
    }

    protected function timezone(): string
    {
        return ($this->configuration ?? $this->configuration())->get('feeds.date.timezone');
    }

    protected function configuration(): Repository
    {
        if (! $this->transforming) {
            return config();
        }

        return $this->configuration ??= config();
    }
}
