<?php

declare(strict_types=1);

use Workbench\App\Data\NewsFakeData;
use Workbench\App\Feeds\SplitXmlFeed;

test('export', function () {
    createNews(...NewsFakeData::toArray());

    expectFeedSnapshot(SplitXmlFeed::class, indexes: [1, 2]);
});
