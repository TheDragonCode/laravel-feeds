<?php

declare(strict_types=1);

use Carbon\Carbon;
use DragonCode\LaravelFeed\Attributes\DateFormat;
use DragonCode\LaravelFeed\Contracts\Transformer;
use DragonCode\LaravelFeed\Converters\Converter;
use DragonCode\LaravelFeed\Converters\CsvConverter;
use DragonCode\LaravelFeed\Converters\JsonConverter;
use DragonCode\LaravelFeed\Converters\JsonLinesConverter;
use DragonCode\LaravelFeed\Converters\RssConverter;
use DragonCode\LaravelFeed\Converters\XmlConverter;
use DragonCode\LaravelFeed\Data\ElementData;
use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use DragonCode\LaravelFeed\Services\TransformerService;
use DragonCode\LaravelFeed\Transformers\DateTimeTransformer;

class DateFormatDefaultItem extends FeedItem
{
    public function __construct(
        private readonly array $values,
        private readonly array $itemAttributes = [],
    ) {}

    public function name(): string
    {
        return 'item';
    }

    public function attributes(): array
    {
        return $this->itemAttributes;
    }

    public function toArray(): array
    {
        return $this->values;
    }
}

#[DateFormat('Y-m-d')]
class DateFormatCustomItem extends DateFormatDefaultItem {}

class DateFormatInheritedItem extends DateFormatCustomItem {}

#[DateFormat('H:i:s')]
class DateFormatOverriddenItem extends DateFormatInheritedItem {}

#[DateFormat('')]
class DateFormatEmptyItem extends DateFormatDefaultItem {}

final class DateFormatStringTransformer implements Transformer
{
    public static int $instances = 0;

    public function __construct()
    {
        self::$instances++;
    }

    public function allow(mixed $value): bool
    {
        return is_string($value);
    }

    public function transform(mixed $value): string
    {
        expect(config('feeds.date.format'))->toBe(DATE_ATOM);

        return $value . ':custom';
    }
}

final class DateFormatFallbackTransformer extends DateTimeTransformer
{
    protected function format(): string
    {
        return 'Y';
    }
}

function dateFormatPublishedAt(Converter $converter, string $content): string
{
    return match (true) {
        $converter instanceof CsvConverter => parseCsv($content)[0][0],
        $converter instanceof XmlConverter => parseXmlDocument($content)->getElementsByTagName('published_at')->item(0)->textContent,
        default                            => parseJsonDocument(rtrim($content, ','))['published_at'],
    };
}

dataset('date format converters', [
    JsonConverter::class,
    JsonLinesConverter::class,
    CsvConverter::class,
    XmlConverter::class,
    RssConverter::class,
]);

test('isolates date formats between items and feed information', function (string $class) {
    config()->set('feeds.date', ['format' => 'd/m/Y H:i', 'timezone' => '+03:00']);

    $converter = app($class);
    $date      = new DateTimeImmutable('2026-07-28T23:30:00+00:00');

    foreach ([
        [DateFormatCustomItem::class, '2026-07-29'],
        [DateFormatDefaultItem::class, '29/07/2026 02:30'],
        [DateFormatOverriddenItem::class, '02:30:00'],
        [DateFormatInheritedItem::class, '2026-07-29'],
        [DateFormatCustomItem::class, '2026-07-29'],
    ] as [$item, $expected]) {
        expect(dateFormatPublishedAt($converter, $converter->item(new $item(['published_at' => $date]), true)))
            ->toBe($expected);
    }

    expect(dateFormatPublishedAt($converter, $converter->info(['published_at' => $date], true)))
        ->toBe('29/07/2026 02:30')
        ->and(config('feeds.date'))
        ->toBe(['format' => 'd/m/Y H:i', 'timezone' => '+03:00']);
})->with('date format converters');

test('formats nested dates without changing source objects', function (string $class) {
    config()->set('feeds.date.timezone', '+03:00');

    $dates = [
        new DateTimeImmutable('2026-07-28T23:30:00+00:00'),
        new DateTime('2026-07-28T23:30:00+00:00'),
        Carbon::parse('2026-07-28T23:30:00+00:00'),
    ];
    $before = array_map(fn (DateTimeInterface $date): string => $date->format(DATE_ATOM), $dates);
    $item   = new DateFormatCustomItem(['dates' => $dates, 'nested' => ['published_at' => $dates[0]]]);
    $data   = parseJsonDocument(app($class)->item($item, true));

    expect($data)->toBe([
        'dates'  => ['2026-07-29', '2026-07-29', '2026-07-29'],
        'nested' => ['published_at' => '2026-07-29'],
    ])
        ->and(array_map(fn (DateTimeInterface $date): string => $date->format(DATE_ATOM), $dates))
        ->toBe($before)
        ->and($item->toArray()['dates'])
        ->toBe($dates);
})->with([JsonConverter::class, JsonLinesConverter::class]);

test('formats item and nested XML attributes while preserving root dates', function (string $class) {
    $converter = app($class);
    $date      = new DateTimeImmutable('2026-07-28T12:30:00+00:00');
    $item      = new DateFormatCustomItem([
        'meta' => ['@attributes' => ['published_at' => $date], '@value' => $date],
    ], ['published_at' => $date]);

    $document = parseXmlDocument($converter->item($item, true));
    $meta     = $document->getElementsByTagName('meta')->item(0);

    $feed = mock(Feed::class);
    $feed->shouldReceive('root')->once()->andReturn(new ElementData('feed', ['published_at' => $date]));

    $root = parseXmlDocument($converter->root($feed) . '</feed>')->documentElement;

    expect($document->documentElement->getAttribute('published_at'))
        ->toBe('2026-07-28')
        ->and($meta->getAttribute('published_at'))
        ->toBe('2026-07-28')
        ->and($meta->textContent)
        ->toBe('2026-07-28')
        ->and($root->getAttribute('published_at'))
        ->toBe('2026-07-28T12:30:00+00:00');
})->with([XmlConverter::class, RssConverter::class]);

test('runs item date formatting before feed and global transformers', function () {
    DateFormatStringTransformer::$instances = 0;

    $converter = new JsonConverter(JSON_THROW_ON_ERROR, false, new TransformerService(app(), [
        DateTimeTransformer::class,
        DateFormatStringTransformer::class,
    ]));
    $converter->prependTransformers([DateFormatFallbackTransformer::class]);

    $date = new DateTimeImmutable('2026-07-28T12:30:00+00:00');

    expect(parseJsonDocument($converter->item(new DateFormatCustomItem(['published_at' => $date]), true)))
        ->toBe(['published_at' => '2026-07-28:custom'])
        ->and(parseJsonDocument($converter->item(new DateFormatDefaultItem(['published_at' => $date]), true)))
        ->toBe(['published_at' => '2026:custom'])
        ->and(parseJsonDocument($converter->item(new DateFormatOverriddenItem(['published_at' => $date]), true)))
        ->toBe(['published_at' => '12:30:00:custom'])
        ->and(parseJsonDocument($converter->item(new DateFormatEmptyItem(['published_at' => $date]), true)))
        ->toBe(['published_at' => ':custom'])
        ->and(parseJsonDocument(rtrim($converter->info(['published_at' => $date], true), ',')))
        ->toBe(['published_at' => '2026:custom'])
        ->and(DateFormatStringTransformer::$instances)
        ->toBe(1);
});

test('restores the default pipeline after an item fails to encode', function () {
    config()->set('feeds.converters.json.options', JSON_THROW_ON_ERROR);

    $converter = app(JsonConverter::class);
    $date      = new DateTimeImmutable('2026-07-28T12:30:00+00:00');
    $item      = new DateFormatCustomItem(['published_at' => $date, 'invalid' => NAN]);

    expect(fn () => $converter->item($item, true))->toThrow(JsonException::class);

    expect(dateFormatPublishedAt($converter, $converter->info(['published_at' => $date], true)))
        ->toBe('2026-07-28T12:30:00+00:00')
        ->and(dateFormatPublishedAt($converter, $converter->item(new DateFormatDefaultItem(['published_at' => $date]), true)))
        ->toBe('2026-07-28T12:30:00+00:00')
        ->and(dateFormatPublishedAt($converter, $converter->item(new DateFormatOverriddenItem(['published_at' => $date]), true)))
        ->toBe('12:30:00');
});
