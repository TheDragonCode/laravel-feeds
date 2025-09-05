<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Enums\FeedFormatEnum;
use Workbench\App\Data\NewsFakeData;
use Workbench\App\Feeds\JsonLinesInfoFeed;

test('export', function (bool $pretty) {
    setPrettyXml($pretty);

    createNews(...NewsFakeData::toArray());

    expectFeedSnapshot(JsonLinesInfoFeed::class, FeedFormatEnum::JsonLines);
})->with('boolean');
