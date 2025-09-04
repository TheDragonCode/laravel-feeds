<?php

declare(strict_types=1);

namespace Workbench\App\Feeds\Docs;

use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Workbench\App\Feeds\Docs\Items\AttributeFeedItem;
use Workbench\App\Models\User;

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

    public function filename(): string
    {
        return '../../../../../../../../../docs/snippets/advanced-element-attribute.xml';
    }
}
