<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Enums\FeedFormatEnum;
use Workbench\App\Data\ManyFilesData;
use Workbench\App\Feeds\SplitJsonLinesFeed;

test('export', function () {
    createNews(...ManyFilesData::toArray());

    expectFeedSnapshot(SplitJsonLinesFeed::class, FeedFormatEnum::JsonLines, indexes: [1, 2, 3]);
});
