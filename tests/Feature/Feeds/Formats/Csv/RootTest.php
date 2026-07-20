<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Enums\FeedFormatEnum;
use Workbench\App\Data\CsvFakeData;
use Workbench\App\Feeds\CsvRootFeed;

test('export', function (bool $pretty) {
    setPrettyXml($pretty);

    createNews(...CsvFakeData::toArray());

    expectFeedSnapshot(CsvRootFeed::class, FeedFormatEnum::Csv);
})->with('boolean');
