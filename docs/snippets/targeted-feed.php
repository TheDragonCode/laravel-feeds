<?php

declare(strict_types=1);

namespace App\Feeds;

use App\Models\Product;
use DragonCode\LaravelFeed\Concerns\InteractsWithFeedTargets;
use DragonCode\LaravelFeed\Contracts\HasFeedTargets;
use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Feeds\FeedTarget;
use Illuminate\Database\Eloquent\Builder;

class ProductFeed extends Feed implements HasFeedTargets
{
    use InteractsWithFeedTargets;

    private const TARGETS = [
        'available' => ['in_stock' => true],
        'unavailable' => ['in_stock' => false],
    ];

    public function targets(): iterable
    {
        foreach (array_keys(self::TARGETS) as $key) {
            yield $this->makeTarget($key);
        }
    }

    public function findTarget(string $key): ?FeedTarget
    {
        return isset(self::TARGETS[$key]) ? $this->makeTarget($key) : null;
    }

    public function builder(): Builder
    {
        return Product::query()->where(
            'in_stock',
            $this->target()->parameters['in_stock'],
        );
    }

    public function filename(): string
    {
        return "products/{$this->target()->key}.xml";
    }

    private function makeTarget(string $key): FeedTarget
    {
        return new FeedTarget(
            key       : $key,
            parameters: self::TARGETS[$key],
        );
    }
}
