<?php

declare(strict_types=1);

namespace Workbench\App\Feeds;

use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Workbench\App\Feeds\Items\FailedFeedItem;
use Workbench\App\Models\User;

class FailedFeed extends Feed
{
    public function builder(): Builder
    {
        return User::query();
    }

    public function item(Model $model): FeedItem
    {
        return new FailedFeedItem($model);
    }
}
