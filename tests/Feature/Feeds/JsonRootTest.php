<?php

declare(strict_types=1);

use Workbench\App\Data\NewsFakeData;
use Workbench\App\Feeds\JsonRootFeed;

test('export', function (bool $pretty) {
    setPrettyXml($pretty);

    createNews(...NewsFakeData::toArray());

    expectFeedSnapshot(JsonRootFeed::class, true);
})->with('boolean');
