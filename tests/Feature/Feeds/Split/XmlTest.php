<?php

declare(strict_types=1);

use Workbench\App\Data\NewsFakeData;
use Workbench\App\Feeds\SplitXmlFeed;

test('export', function () {
    createNews(...NewsFakeData::toArray());

    expectFeedSnapshot(SplitXmlFeed::class, indexes: [1, 2]);

    $feed = app(SplitXmlFeed::class);

    $first  = parseXmlDocument(readFeedFile($feed->path(1)));
    $second = parseXmlDocument(readFeedFile($feed->path(2)));

    expect($first->getElementsByTagName('record_title')->item(0)?->textContent)
        ->toBe('[NEWS]:Some 1')
        ->and($first->getElementsByTagName('record_title')->item(1)?->textContent)
        ->toBe('[NEWS]:Some 2')
        ->and($second->getElementsByTagName('record_title')->item(0)?->textContent)
        ->toBe('[NEWS]:Some 3');
});
