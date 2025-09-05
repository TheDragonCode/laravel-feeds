<?php

declare(strict_types=1);

namespace Workbench\App\Feeds;

use DragonCode\LaravelFeed\Data\ElementData;
use DragonCode\LaravelFeed\Enums\FeedFormatEnum;
use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Feeds\Info\FeedInfo;
use Illuminate\Database\Eloquent\Builder;
use Workbench\App\Feeds\Info\YandexFeedInfo;
use Workbench\App\Models\News;

class JsonInfoFeed extends Feed
{
    protected FeedFormatEnum $format = FeedFormatEnum::Json;

    public function builder(): Builder
    {
        return News::query();
    }

    public function root(): ElementData
    {
        return new ElementData(
            name: null
        );
    }

    public function info(): FeedInfo
    {
        return new YandexFeedInfo;
    }
}
