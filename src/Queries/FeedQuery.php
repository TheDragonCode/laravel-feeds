<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Queries;

use DragonCode\LaravelFeed\Exceptions\FeedNotFoundException;
use DragonCode\LaravelFeed\Exceptions\InvalidFeedModelException;
use DragonCode\LaravelFeed\Models\Feed;
use Illuminate\Database\Eloquent\Builder;

use function config;
use function is_a;
use function is_string;
use function now;

class FeedQuery
{
    public function create(
        string $class,
        string $title,
        string $expression = '0 * * * *',
        bool $isActive = true,
        array $extra = [],
    ): Feed {
        $model = $this->modelClass();

        return $model::create([
            ...$extra,
            'class'      => $class,
            'title'      => $title,
            'expression' => $expression,
            'is_active'  => $isActive,
        ]);
    }

    public function find(int $id): Feed
    {
        $model = $this->modelClass();

        return $model::findOr($id, callback: static fn () => throw new FeedNotFoundException($id));
    }

    public function all(): Builder
    {
        $model = $this->modelClass();

        return $model::query()->orderBy('id');
    }

    public function active(): Builder
    {
        $model = $this->modelClass();

        return $model::query()
            ->where('is_active', true)
            ->orderBy('id');
    }

    public function setLastActivity(string $class): void
    {
        $model = $this->modelClass();

        $model::query()
            ->whereClass($class)
            ->update(['last_activity' => now()]);
    }

    public function delete(int $id): void
    {
        $model = $this->modelClass();

        $model::destroy($id);
    }

    public function deleteByClass(string $class): void
    {
        $model = $this->modelClass();

        $model::withTrashed()
            ->whereClass($class)
            ->forceDelete();
    }

    public function restore(int $id): void
    {
        $model = $this->modelClass();

        $model::query()
            ->whereId($id)
            ->restore();
    }

    protected function modelClass(): string
    {
        $model = config('feeds.model', Feed::class);

        if (! is_string($model) || ! is_a($model, Feed::class, true)) {
            throw new InvalidFeedModelException($model);
        }

        return $model;
    }
}
