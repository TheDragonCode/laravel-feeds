<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Enums\FeedFormatEnum;
use Workbench\App\Data\CsvFakeData;
use Workbench\App\Feeds\CsvRootInfoFeed;

test('export', function (bool $pretty) {
    setPrettyXml($pretty);

    createNews(...CsvFakeData::toArray());

    expectFeedSnapshot(CsvRootInfoFeed::class, FeedFormatEnum::Csv);

    expect(parseCsv(readFeedFile(app(CsvRootInfoFeed::class)->path()))[0])
        ->toBe(['id', 'title', 'content', 'category', 'created_at', 'updated_at']);
})->with('boolean');
