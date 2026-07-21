<?php

declare(strict_types=1);

namespace Tests\Helpers\Benchmark;

use DragonCode\LaravelFeed\Enums\FeedFormatEnum;
use DragonCode\LaravelFeed\Feeds\Feed;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Application;

final class RegressionFeed extends Feed
{
    public function __construct(
        Application $laravel,
        protected Builder $query,
        FeedFormatEnum $format,
    ) {
        parent::__construct($laravel);

        $this->format   = $format;
        $this->filename = 'benchmark/feed.' . $format->value;
    }

    public function builder(): Builder
    {
        return $this->query;
    }
}
