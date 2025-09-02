<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Models\Feed;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;

function enableAllFeeds(): void
{
    Feed::query()->update([
        'is_active' => true,
    ]);
}

function disableFeeds(array|string $classes): void
{
    Feed::query()
        ->whereIn('class', Arr::wrap($classes))
        ->update(['is_active' => false]);
}

function getAllFeeds(): Collection
{
    return Feed::get();
}

function findFeed(string $class): Feed
{
    return Feed::query()
        ->whereClass($class)
        ->firstOrFail();
}
