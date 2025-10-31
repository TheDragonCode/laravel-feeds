<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Enums\FeedFormatEnum;
use Workbench\App\Data\NewsFakeData;
use Workbench\App\Feeds\SplitJsonFeed;

test('export', function () {
    setPrettyXml(false);

    createNews(...NewsFakeData::toArray());

    expectFeedSnapshot(SplitJsonFeed::class, FeedFormatEnum::Json, indexes: [1, 2]);
});
