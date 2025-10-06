<?php

declare(strict_types=1);

namespace Workbench\App\Feeds;

use DragonCode\LaravelFeed\Enums\FeedFormatEnum;
use DragonCode\LaravelFeed\Feeds\Feed;
use Illuminate\Database\Eloquent\Builder;
use Workbench\App\Models\News;

class SplitPerFileFeed extends Feed
{
    protected FeedFormatEnum $format = FeedFormatEnum::JsonLines;

    public function builder(): Builder
    {
        return News::query();
    }

    public function perFile(): int
    {
        return 2;
    }
}
