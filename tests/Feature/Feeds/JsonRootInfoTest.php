<?php

declare(strict_types=1);

use Workbench\App\Data\NewsFakeData;
use Workbench\App\Feeds\JsonRootInfoFeed;

test('export', function (bool $pretty) {
    setPrettyXml($pretty);

    createNews(...NewsFakeData::toArray());

    expectFeedSnapshot(JsonRootInfoFeed::class, true);
})->with('boolean');
