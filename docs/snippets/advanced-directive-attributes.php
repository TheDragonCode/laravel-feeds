<?php

declare(strict_types=1);

namespace App\Feeds;

use App\Feeds\Info\AttributesDirectiveFeedInfo;
use App\Feeds\Items\AttributesDirectiveFeedItem;
use App\Models\User;
use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Feeds\Info\FeedInfo;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AttributesDirectiveFeed extends Feed
{
    public function builder(): Builder
    {
        return User::query();
    }

    public function info(): FeedInfo
    {
        return new AttributesDirectiveFeedInfo;
    }

    public function item(Model $model): FeedItem
    {
        return new AttributesDirectiveFeedItem($model);
    }

    public function filename(): string
    {
        return '../../../../../../../../../docs/snippets/advanced-directive-attributes.xml';
    }
}
