<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Converters\CsvConverter;
use DragonCode\LaravelFeed\Converters\JsonConverter;
use DragonCode\LaravelFeed\Converters\JsonLinesConverter;
use DragonCode\LaravelFeed\Converters\RssConverter;
use DragonCode\LaravelFeed\Converters\XmlConverter;
use DragonCode\LaravelFeed\Data\OptionalData;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use Spatie\LaravelData\Optional;

test('omits optional values from JSON formats', function (string $converter, object $optional) {
    $item = mock(FeedItem::class);
    $item->shouldReceive('toArray')->once()->andReturn([
        'id'       => 123,
        'optional' => $optional,
        'meta'     => [
            'name'     => 'Laravel Feeds',
            'optional' => $optional,
        ],
        'tags' => [
            'laravel',
            $optional,
            'feeds',
        ],
    ]);

    $content = app($converter)->item($item, true);
    $actual  = $converter === JsonConverter::class
        ? parseJsonDocument($content)
        : parseJsonLines($content)[0];

    expect($actual)->toBe([
        'id'   => 123,
        'meta' => [
            'name' => 'Laravel Feeds',
        ],
        'tags' => [
            'laravel',
            'feeds',
        ],
    ]);
})->with([
    JsonConverter::class,
    JsonLinesConverter::class,
])->with([
    'Laravel Feeds optional'       => new OptionalData,
    'Spatie Laravel Data optional' => Optional::create(),
]);

test('omits optional values from XML formats', function (string $converter, object $optional) {
    $item = mock(FeedItem::class);
    $item->shouldReceive('name')->andReturn('item');
    $item->shouldReceive('attributes')->once()->andReturn([]);
    $item->shouldReceive('toArray')->once()->andReturn([
        'id'       => 123,
        'optional' => $optional,
        'meta'     => [
            'name'     => 'Laravel Feeds',
            'optional' => $optional,
        ],
    ]);

    $document = parseXmlDocument(
        app($converter)->item($item, true)
    );

    expect($document->getElementsByTagName('optional')->length)
        ->toBe(0)
        ->and($document->getElementsByTagName('id')->item(0)?->textContent)
        ->toBe('123')
        ->and($document->getElementsByTagName('name')->item(0)?->textContent)
        ->toBe('Laravel Feeds');
})->with([
    XmlConverter::class,
    RssConverter::class,
])->with([
    'Laravel Feeds optional'       => new OptionalData,
    'Spatie Laravel Data optional' => Optional::create(),
]);

test('writes optional values as empty CSV fields', function (object $optional) {
    $item = mock(FeedItem::class);
    $item->shouldReceive('toArray')->once()->andReturn([
        'id'       => 123,
        'optional' => $optional,
        'name'     => 'Laravel Feeds',
    ]);

    $content = app(CsvConverter::class)->item($item, true);

    expect(parseCsv($content))->toBe([[
        '123',
        '',
        'Laravel Feeds',
    ]]);
})->with([
    'Laravel Feeds optional'       => new OptionalData,
    'Spatie Laravel Data optional' => Optional::create(),
]);
