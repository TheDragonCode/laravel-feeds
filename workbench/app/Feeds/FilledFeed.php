<?php

declare(strict_types=1);

namespace Workbench\App\Feeds;

use DragonCode\LaravelFeed\FeedItem;
use DragonCode\LaravelFeed\Items\Feed;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Workbench\App\Feeds\Items\NewsFeedItem;
use Workbench\App\Models\News;

use function class_basename;
use function now;

class FilledFeed extends Feed
{
    public function builder(): Builder
    {
        return News::query()->where('updated_at', '>', now()->subDay());
    }

    public function rootItem(): ?string
    {
        return class_basename($this);
    }

    public function filename(): string
    {
        return 'partial/feed.xml';
    }

    public function item(Model $model): FeedItem
    {
        return new NewsFeedItem($model);
    }
}
