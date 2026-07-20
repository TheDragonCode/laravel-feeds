<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Enums\FeedFormatEnum;
use Workbench\App\Data\CsvFakeData;
use Workbench\App\Feeds\CsvInfoFeed;

test('export', function (bool $pretty) {
    setPrettyXml($pretty);

    createNews(...CsvFakeData::toArray());

    expectFeedSnapshot(CsvInfoFeed::class, FeedFormatEnum::Csv);

    expect(parseCsv(file_get_contents(app(CsvInfoFeed::class)->path()))[0])
        ->toBe(['id', 'title', 'content', 'category', 'created_at', 'updated_at']);
})->with('boolean');
