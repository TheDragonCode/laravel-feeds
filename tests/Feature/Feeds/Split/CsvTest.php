<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Enums\FeedFormatEnum;
use Workbench\App\Data\NewsFakeData;
use Workbench\App\Feeds\SplitCsvFeed;

test('export', function () {
    createNews(...NewsFakeData::toArray());

    expectFeedSnapshot(SplitCsvFeed::class, FeedFormatEnum::Csv, indexes: [1, 2]);
});
