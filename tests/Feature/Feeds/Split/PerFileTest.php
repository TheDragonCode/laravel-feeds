<?php

declare(strict_types=1);

use Workbench\App\Data\NewsFakeData;
use Workbench\App\Feeds\SplitPerFileFeed;

test('export', function () {
    createNews(...NewsFakeData::toArray());

    expectFeedSnapshot(SplitPerFileFeed::class, indexes: [1, 2]);
});
