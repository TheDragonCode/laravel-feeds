<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Enums\FeedFormatEnum;
use Workbench\App\Data\CsvFakeData;
use Workbench\App\Feeds\CsvFeed;

test('export', function (bool $pretty) {
    setPrettyXml($pretty);

    createNews(...CsvFakeData::toArray());

    expectFeedSnapshot(CsvFeed::class, FeedFormatEnum::Csv);

    $rows = parseCsv(
        file_get_contents(app(CsvFeed::class)->path())
    );

    expect($rows[0][1])
        ->toBe('Товар; "один"')
        ->and($rows[0][2])
        ->toBe("Первая строка\r\nВторая строка")
        ->and($rows[0][3])
        ->toBe('Новости UTF-8')
        ->and($rows[1][2])
        ->toBe('')
        ->and($rows[1][3])
        ->toBe('')
        ->and($rows[2][5])
        ->toBe('');
})->with('boolean');
