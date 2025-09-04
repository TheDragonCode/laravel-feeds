<?php

declare(strict_types=1);

namespace App\Feeds;

use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Feeds\Items\AttributeFeedItem;
use App\Models\User;

class AttributeFeed extends Feed
{
    public function builder(): Builder
    {
        return User::query();
    }

    public function item(Model $model): FeedItem
    {
        return new AttributeFeedItem($model);
    }
}
