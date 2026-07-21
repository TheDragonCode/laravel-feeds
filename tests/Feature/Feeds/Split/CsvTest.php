<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Enums\FeedFormatEnum;
use Workbench\App\Data\CsvFakeData;
use Workbench\App\Feeds\SplitCsvFeed;

test('export', function () {
    createNews(...CsvFakeData::toArray());

    expectFeedSnapshot(SplitCsvFeed::class, FeedFormatEnum::Csv, indexes: [1, 2]);

    $feed = app(SplitCsvFeed::class);

    $first  = parseCsv(readFeedFile($feed->path(1)));
    $second = parseCsv(readFeedFile($feed->path(2)));

    expect($first)
        ->toHaveCount(2)
        ->and($first[0][2])
        ->toBe("Первая строка\r\nВторая строка")
        ->and($second)
        ->toHaveCount(1)
        ->and($second[0][5])
        ->toBe('');
});
