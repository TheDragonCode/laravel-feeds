<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Queries;

use DragonCode\LaravelFeed\Exceptions\FeedNotFoundException;
use DragonCode\LaravelFeed\Models\Feed;
use DragonCode\LaravelFeed\Scheduling\Expression;
use Illuminate\Container\Attributes\Config;
use Illuminate\Database\Eloquent\Builder;

use function now;

class FeedQuery
{
    public function __construct(
        #[Config('feeds.model', Feed::class)]
        protected string $model,
    ) {}

    public function create(
        string $class,
        string $title,
        Expression|string $expression = '0 * * * *',
        bool $isActive = true,
        array $extra = [],
    ): Feed {
        return $this->model::create([
            'class'      => $class,
            'title'      => $title,
            'expression' => $expression,
            'is_active'  => $isActive,
            ...$extra,
        ]);
    }

    public function find(int $id): Feed
    {
        return $this->model::findOr($id, callback: static fn () => throw new FeedNotFoundException($id));
    }

    public function all(): Builder
    {
        return $this->model::query()->orderBy('id');
    }

    public function active(): Builder
    {
        return $this->model::query()
            ->where('is_active', true)
            ->orderBy('id');
    }

    public function setLastActivity(string $class): void
    {
        $this->model::query()
            ->whereClass($class)
            ->update(['last_activity' => now()]);
    }

    public function delete(int $id): void
    {
        $this->model::destroy($id);
    }

    public function deleteByClass(string $class): void
    {
        $this->model::withTrashed()
            ->whereClass($class)
            ->forceDelete();
    }

    public function restore(int $id): void
    {
        $this->model::query()
            ->whereId($id)
            ->restore();
    }
}
