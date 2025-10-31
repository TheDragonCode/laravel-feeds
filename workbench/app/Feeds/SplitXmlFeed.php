<?php

declare(strict_types=1);

namespace Workbench\App\Feeds;

use DragonCode\LaravelFeed\Data\ElementData;
use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Workbench\App\Feeds\Items\NewsFeedItem;
use Workbench\App\Models\News;

use function class_basename;

class SplitXmlFeed extends Feed
{
    public function builder(): Builder
    {
        return News::query();
    }

    public function perFile(): int
    {
        return 2;
    }

    public function root(): ElementData
    {
        return new ElementData(
            class_basename($this)
        );
    }

    public function item(Model $model): FeedItem
    {
        return new NewsFeedItem($model);
    }
}
