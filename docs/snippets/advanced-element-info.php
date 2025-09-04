<?php

declare(strict_types=1);

namespace App\Feeds;

use App\Feeds\Info\InfoMethodFeedInfo;
use App\Models\User;
use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Feeds\Info\FeedInfo;
use Illuminate\Database\Eloquent\Builder;

class InfoMethodFeed extends Feed
{
    public function builder(): Builder
    {
        return User::query();
    }

    public function info(): FeedInfo
    {
        return new InfoMethodFeedInfo;
    }

    public function filename(): string
    {
        return '../../../../../../../../../docs/snippets/advanced-element-info.xml';
    }
}
