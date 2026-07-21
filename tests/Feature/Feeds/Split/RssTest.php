<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Enums\FeedFormatEnum;
use Workbench\App\Data\NewsFakeData;
use Workbench\App\Feeds\SplitRssFeed;

test('export', function () {
    createNews(...NewsFakeData::toArray());

    expectFeedSnapshot(SplitRssFeed::class, FeedFormatEnum::Rss, indexes: [1, 2]);

    $feed = app(SplitRssFeed::class);

    $first  = parseXmlDocument(readFeedFile($feed->path(1)));
    $second = parseXmlDocument(readFeedFile($feed->path(2)));

    $firstTitles  = $first->getElementsByTagName('title');
    $secondTitles = $second->getElementsByTagName('title');

    expect($firstTitles->item(0)?->textContent)
        ->toBe('Some 1')
        ->and($firstTitles->item(1)?->textContent)
        ->toBe('Some 2')
        ->and($secondTitles->item(0)?->textContent)
        ->toBe('Some 3');
});
