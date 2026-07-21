<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Enums\FeedFormatEnum;
use Workbench\App\Data\NewsFakeData;
use Workbench\App\Feeds\SplitJsonLinesFeed;

test('export', function () {
    createNews(...NewsFakeData::toArray());

    expectFeedSnapshot(SplitJsonLinesFeed::class, FeedFormatEnum::JsonLines, indexes: [1, 2]);

    $feed = app(SplitJsonLinesFeed::class);

    $first  = parseJsonLines(readFeedFile($feed->path(1)));
    $second = parseJsonLines(readFeedFile($feed->path(2)));

    expect(array_column($first, 'title'))
        ->toBe(['Some 1', 'Some 2'])
        ->and(array_column($second, 'title'))
        ->toBe(['Some 3']);
});
