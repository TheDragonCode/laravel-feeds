<?php

declare(strict_types=1);

namespace Workbench\App\Feeds\Docs;

use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Feeds\Info\FeedInfo;
use Illuminate\Database\Eloquent\Builder;
use Workbench\App\Feeds\Docs\Info\InfoMethodFeedInfo;
use Workbench\App\Models\User;

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
