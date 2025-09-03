<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Queries;

use DragonCode\LaravelFeed\Exceptions\FeedNotFoundException;
use DragonCode\LaravelFeed\Models\Feed;
use Illuminate\Database\Eloquent\Builder;

use function now;

class FeedQuery
{
    public function create(
        string $class,
        string $title,
        string $expression = '* * * * *',
        bool $isActive = true,
    ): Feed {
        return Feed::create([
            'class'      => $class,
            'title'      => $title,
            'expression' => $expression,
            'is_active'  => $isActive,
        ]);
    }

    public function find(int $id): Feed
    {
        return Feed::findOr($id, callback: static fn () => throw new FeedNotFoundException($id));
    }

    public function active(): Builder
    {
        return Feed::query()
            ->where('is_active', true)
            ->orderBy('id');
    }

    public function setLastActivity(string $class): void
    {
        Feed::query()
            ->whereClass($class)
            ->update(['last_activity' => now()]);
    }

    public function delete(int $id): void
    {
        Feed::destroy($id);
    }

    public function restore(int $id): void
    {
        Feed::query()
            ->whereId($id)
            ->restore();
    }
}
