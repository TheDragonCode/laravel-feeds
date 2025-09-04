<?php

declare(strict_types=1);

namespace Workbench\App\Feeds\Docs;

use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Feeds\Info\FeedInfo;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Workbench\App\Feeds\Docs\Info\AttributesDirectiveFeedInfo;
use Workbench\App\Feeds\Docs\Items\AttributesDirectiveFeedItem;
use Workbench\App\Models\User;

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
