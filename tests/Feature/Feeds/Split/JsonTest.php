<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Enums\FeedFormatEnum;
use Workbench\App\Data\NewsFakeData;
use Workbench\App\Feeds\SplitJsonFeed;

test('export', function () {
    setPrettyXml(false);

    createNews(...NewsFakeData::toArray());

    expectFeedSnapshot(SplitJsonFeed::class, FeedFormatEnum::Json, indexes: [1, 2]);

    $feed = app(SplitJsonFeed::class);

    $first  = parseJsonDocument(readFeedFile($feed->path(1)));
    $second = parseJsonDocument(readFeedFile($feed->path(2)));

    expect(array_column($first, 'title'))
        ->toBe(['Some 1', 'Some 2'])
        ->and(array_column($second, 'title'))
        ->toBe(['Some 3']);
});
