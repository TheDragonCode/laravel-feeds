<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Enums\FeedFormatEnum;
use Workbench\App\Data\NewsFakeData;
use Workbench\App\Feeds\SplitRssFeed;

test('export', function () {
    createNews(...NewsFakeData::toArray());

    expectFeedSnapshot(SplitRssFeed::class, FeedFormatEnum::Rss, indexes: [1, 2]);
});
