<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Converters\CsvConverter;
use DragonCode\LaravelFeed\Exceptions\InvalidCsvRowException;
use DragonCode\LaravelFeed\Exceptions\InvalidCsvValueException;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;

test('round trips supported scalar values', function () {
    $item = mock(FeedItem::class);
    $item->shouldReceive('toArray')->once()->andReturn([
        'delimiter' => 'value;with;delimiter',
        'quotes'    => 'value "with" quotes',
        'carriage'  => "first\rsecond",
        'line_feed' => "first\nsecond",
        'crlf'      => "first\r\nsecond",
        'utf8'      => 'Привет, мир',
        'empty'     => '',
        'null'      => null,
    ]);

    $content = app(CsvConverter::class)->item($item, false);

    expect($content)->toBeCsv();

    expect(parseCsv($content))->toBe([[
        'value;with;delimiter',
        'value "with" quotes',
        "first\rsecond",
        "first\nsecond",
        "first\r\nsecond",
        'Привет, мир',
        '',
        '',
    ]]);
});

test('uses configured CSV controls', function () {
    config()->set('feeds.converters.csv', [
        'delimiter'   => '|',
        'enclosure'   => "'",
        'escape'      => '',
        'line_ending' => "\r\n",
    ]);

    $item = mock(FeedItem::class);
    $item->shouldReceive('toArray')->once()->andReturn([
        'delimiter' => 'value|with|delimiter',
        'enclosure' => "value 'with' enclosure",
    ]);

    $converter = app(CsvConverter::class);
    $content   = $converter->item($item, true);

    expect($converter->lineEnding())
        ->toBe("\r\n")
        ->and(parseCsv($content))
        ->toBe([[
            'value|with|delimiter',
            "value 'with' enclosure",
        ]]);
});

test('keeps the first row column order', function () {
    $first = mock(FeedItem::class);
    $first->shouldReceive('toArray')->once()->andReturn([
        'id'    => 1,
        'title' => 'First',
    ]);

    $second = mock(FeedItem::class);
    $second->shouldReceive('toArray')->once()->andReturn([
        'title' => 'Second',
        'id'    => 2,
    ]);

    $converter = app(CsvConverter::class);

    $content = $converter->item($first, false)
        . $converter->lineEnding()
        . $converter->item($second, true);

    expect(parseCsv($content))->toBe([
        ['1', 'First'],
        ['2', 'Second'],
    ]);
});

test('rejects rows with different columns', function () {
    $first = mock(FeedItem::class);
    $first->shouldReceive('toArray')->once()->andReturn([
        'id'    => 1,
        'title' => 'First',
    ]);

    $second = mock(FeedItem::class);
    $second->shouldReceive('toArray')->once()->andReturn([
        'id'      => 2,
        'content' => 'Second',
    ]);

    $converter = app(CsvConverter::class);
    $converter->item($first, false);

    expect(fn () => $converter->item($second, true))
        ->toThrow(
            InvalidCsvRowException::class,
            'CSV row columns do not match the established schema. Expected [id, title], got [id, content].'
        );
});

test('rejects nested values', function () {
    $item = mock(FeedItem::class);
    $item->shouldReceive('toArray')->once()->andReturn([
        'id'   => 1,
        'meta' => ['value'],
    ]);

    expect(fn () => app(CsvConverter::class)->item($item, true))
        ->toThrow(
            InvalidCsvValueException::class,
            'CSV column [meta] contains a nested value. Nested arrays are not supported.'
        );
});
