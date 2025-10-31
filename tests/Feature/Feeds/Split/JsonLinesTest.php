<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Enums\FeedFormatEnum;
use Workbench\App\Data\NewsFakeData;
use Workbench\App\Feeds\SplitJsonLinesFeed;

test('export', function () {
    createNews(...NewsFakeData::toArray());

    expectFeedSnapshot(SplitJsonLinesFeed::class, FeedFormatEnum::JsonLines, indexes: [1, 2]);
});
