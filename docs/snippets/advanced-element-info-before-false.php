<?php

declare(strict_types=1);

namespace App\Feeds;

use DragonCode\LaravelFeed\Data\ElementData;
use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Feeds\Info\FeedInfo;
use Illuminate\Database\Eloquent\Builder;
use App\Feeds\Info\InfoMethodFeedInfo;
use App\Models\User;

class InfoMethodBeforeFalseTest extends Feed
{
    public function builder(): Builder
    {
        return User::query();
    }

    public function root(): ElementData
    {
        return new ElementData(
            name      : 'info_method',
            beforeInfo: false
        );
    }

    public function info(): FeedInfo
    {
        return new InfoMethodFeedInfo;
    }
}
