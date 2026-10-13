<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Attributes\Config;
use DragonCode\LaravelFeed\Converters\Converter;
use DragonCode\LaravelFeed\Converters\CsvConverter;
use DragonCode\LaravelFeed\Converters\JsonLinesConverter;
use DragonCode\LaravelFeed\Converters\XmlConverter;
use DragonCode\LaravelFeed\Data\ElementData;
use DragonCode\LaravelFeed\Enums\FeedFormatEnum;
use DragonCode\LaravelFeed\Exceptions\FeedGenerationException;
use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use DragonCode\LaravelFeed\Helpers\ConverterHelper;
use DragonCode\LaravelFeed\Services\GeneratorService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Storage;
use Workbench\App\Models\News;

class ConverterOptionsFeed extends Feed
{
    protected FeedFormatEnum $format = FeedFormatEnum::Csv;

    protected string $storage = 'converter-options';

    public function builder(): Builder
    {
        return News::query();
    }

    public function item(Model $model): FeedItem
    {
        return new ConverterOptionsItem($model);
    }

    public function root(): ElementData
    {
        return new ElementData;
    }
}

final class ConverterOptionsItem extends FeedItem
{
    public function toArray(): array
    {
        return $this->model->only(['title', 'content']);
    }
}

#[Config('delimiter', ',')]
#[Config('enclosure', "'")]
#[Config('escape', '')]
#[Config('lineEnding', "\r\n")]
class CustomizedCsvOptionsFeed extends ConverterOptionsFeed {}

#[Config('delimiter', '|')]
final class PartialCsvOptionsFeed extends ConverterOptionsFeed {}

#[Config('delimiter', '^')]
#[Config('delimiter', '|')]
#[Config('lineEnding', "\n")]
class InheritedCsvOptionsFeed extends CustomizedCsvOptionsFeed {}

final class UnannotatedCsvOptionsFeed extends InheritedCsvOptionsFeed {}

#[Config('options', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)]
#[Config('pretty', true)]
class PrettyJsonOptionsFeed extends ConverterOptionsFeed
{
    protected FeedFormatEnum $format = FeedFormatEnum::Json;
}

#[Config('options', 0)]
#[Config('pretty', false)]
final class CompactJsonOptionsFeed extends PrettyJsonOptionsFeed {}

#[Config('options', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)]
final class JsonLinesOptionsFeed extends ConverterOptionsFeed
{
    protected FeedFormatEnum $format = FeedFormatEnum::JsonLines;
}

#[Config('pretty', true)]
class PrettyXmlOptionsFeed extends ConverterOptionsFeed
{
    protected FeedFormatEnum $format = FeedFormatEnum::Xml;
}

final class PrettyRssOptionsFeed extends PrettyXmlOptionsFeed
{
    protected FeedFormatEnum $format = FeedFormatEnum::Rss;
}

final class SplitCsvOptionsFeed extends CustomizedCsvOptionsFeed
{
    public function perFile(): int
    {
        return 1;
    }
}

final class BoundOptionsCsvConverter extends CsvConverter {}

final class LegacyOptionsConverterHelper extends ConverterHelper
{
    public array $calls = [];

    public function get(FeedFormatEnum $format, array $transformers = []): Converter
    {
        $this->calls[] = 'get';

        return parent::get($format, $transformers);
    }

    protected function resolve(FeedFormatEnum $format): Converter
    {
        $this->calls[] = 'resolve';

        return parent::resolve($format);
    }
}

final class LegacyGetOptionsConverterHelper extends ConverterHelper
{
    public function get(FeedFormatEnum $format, array $transformers = []): Converter
    {
        return $this->container->make(BoundOptionsCsvConverter::class)->prependTransformers($transformers);
    }
}

class LegacyResolveOptionsConverterHelper extends ConverterHelper
{
    protected function resolve(FeedFormatEnum $format): Converter
    {
        return $this->container->make(BoundOptionsCsvConverter::class);
    }
}

class AwareOptionsConverterHelper extends LegacyResolveOptionsConverterHelper
{
    protected function resolveWithOptions(FeedFormatEnum $format, array $options = []): Converter
    {
        return $this->container->make(BoundOptionsCsvConverter::class, $options);
    }
}

final class InheritedAwareOptionsConverterHelper extends AwareOptionsConverterHelper {}

beforeEach(function () {
    Storage::fake('converter-options');
});

test('uses per-feed CSV options and global defaults without leaking configuration', function () {
    config()->set('feeds.converters.csv', [
        'delimiter'   => ';',
        'enclosure'   => '"',
        'escape'      => '\\',
        'line_ending' => '~',
    ]);

    createNews(
        ['title' => 'First, item', 'content' => 'first'],
        ['title' => 'Second, item', 'content' => 'second'],
    );

    $configuration = config('feeds');
    $generator     = app(GeneratorService::class);

    foreach ([
        CustomizedCsvOptionsFeed::class => "'First, item',first\r\n'Second, item',second",
        PartialCsvOptionsFeed::class    => '"First, item"|first~"Second, item"|second',
        ConverterOptionsFeed::class     => '"First, item";first~"Second, item";second',
    ] as $class => $expected) {
        $feed = app($class);

        $generator->feed($feed);

        expect($feed->storage()->get($feed->storagePath()))
            ->toBe($expected)
            ->and(config('feeds'))
            ->toBe($configuration);
    }
});

test('inherits converter options and lets child keys override parent keys', function () {
    $feed = app(UnannotatedCsvOptionsFeed::class);

    createNews(
        ['title' => 'First item', 'content' => 'first'],
        ['title' => 'Second item', 'content' => 'second'],
    );

    app(GeneratorService::class)->feed($feed);

    expect(app(ConverterOptionsFeed::class)->converterOptions())
        ->toBe([])
        ->and($feed->converterOptions())
        ->toBe(['delimiter' => '|', 'enclosure' => "'", 'escape' => '', 'lineEnding' => "\n"])
        ->and($feed->storage()->get($feed->storagePath()))
        ->toBe("'First item'|first\n'Second item'|second");
});

test('overrides JSON encoder flags and pretty printing for each feed', function () {
    config()->set('feeds.pretty', false);
    config()->set('feeds.converters.json.options', 0);

    $data = ['title' => 'Новости', 'content' => '/news'];

    createNews($data);

    $configuration = config('feeds');
    $pretty        = app(PrettyJsonOptionsFeed::class);

    app(GeneratorService::class)->feed($pretty);

    $content = $pretty->storage()->get($pretty->storagePath());

    expect($content)
        ->toContain('    "title": "Новости"')
        ->toContain('    "content": "/news"')
        ->and(json_decode($content, true, flags: JSON_THROW_ON_ERROR))
        ->toBe([$data])
        ->and(config('feeds'))
        ->toBe($configuration);

    config()->set('feeds.pretty', true);

    $compact = app(CompactJsonOptionsFeed::class);

    app(GeneratorService::class)->feed($compact);

    $content = $compact->storage()->get($compact->storagePath());

    expect($content)
        ->toContain(json_encode($data))
        ->not->toContain('    "title"')
        ->and(json_decode($content, true, flags: JSON_THROW_ON_ERROR))
        ->toBe([$data])
        ->and(config('feeds.pretty'))
        ->toBeTrue()
        ->and(config('feeds.converters.json.options'))
        ->toBe(0);
});

test('applies JSON Lines options before stripping pretty printing', function () {
    config()->set('feeds.converters.jsonl.options', 0);

    $data = ['title' => 'Новости', 'content' => '/news'];

    createNews($data, $data);

    $feed          = app(JsonLinesOptionsFeed::class);
    $configuration = config('feeds');

    app(GeneratorService::class)->feed($feed);

    expect($feed->storage()->get($feed->storagePath()))
        ->toBe('{"title":"Новости","content":"/news"}' . PHP_EOL . '{"title":"Новости","content":"/news"}')
        ->and(config('feeds'))
        ->toBe($configuration);
});

test('applies XML pretty printing before creating the DOM document', function (string $class, string $element) {
    config()->set('feeds.pretty', false);

    createNews(['title' => 'Title', 'content' => 'Body']);

    $feed = app($class);

    app(GeneratorService::class)->feed($feed);

    expect($feed->storage()->get($feed->storagePath()))
        ->toContain("<$element>\n  <title>Title</title>\n  <content>Body</content>\n</$element>")
        ->and(config('feeds.pretty'))
        ->toBeFalse();
})->with([
    'XML' => [PrettyXmlOptionsFeed::class, 'news'],
    'RSS' => [PrettyRssOptionsFeed::class, 'item'],
]);

test('preserves converter bindings when passing constructor options', function () {
    app()->bind(CsvConverter::class, BoundOptionsCsvConverter::class);

    $converter = app(ConverterHelper::class)->forFeed(app(PartialCsvOptionsFeed::class));
    $item      = new ConverterOptionsItem(new News(['title' => 'Title', 'content' => 'Body']));

    expect($converter)
        ->toBeInstanceOf(BoundOptionsCsvConverter::class)
        ->and($converter->item($item, true))
        ->toBe('Title|Body');
});

test('preserves existing converter helper overrides for feeds without options', function () {
    $helper = app(LegacyOptionsConverterHelper::class);

    app()->instance(ConverterHelper::class, $helper);

    createNews(['title' => 'Title', 'content' => 'Body']);

    $feed = app(ConverterOptionsFeed::class);

    app(GeneratorService::class)->feed($feed);

    expect($helper->calls)
        ->toBe(['get', 'resolve'])
        ->and($feed->storage()->get($feed->storagePath()))
        ->toBe('Title;Body');
});

test('preserves custom converters from legacy helper hooks for feeds without options', function (string $class) {
    $converter = app($class)->forFeed(app(ConverterOptionsFeed::class));
    $item      = new ConverterOptionsItem(new News(['title' => 'Title', 'content' => 'Body']));

    expect($converter)
        ->toBeInstanceOf(BoundOptionsCsvConverter::class)
        ->and($converter->item($item, true))
        ->toBe('Title;Body');
})->with([
    'get override'     => [LegacyGetOptionsConverterHelper::class],
    'resolve override' => [LegacyResolveOptionsConverterHelper::class],
]);

test('rejects per-feed options when a legacy helper would be bypassed', function (string $class, string $method) {
    $helper = app($class);
    $feed   = app(PartialCsvOptionsFeed::class);

    expect(fn () => $helper->forFeed($feed))
        ->toThrow(LogicException::class, "Converter helper [$class] overrides [$method]. Override resolveWithOptions() to support per-feed options.");

    $feed->storage()->put($feed->storagePath(), 'previous feed');

    app()->instance(ConverterHelper::class, $helper);

    expect(fn () => app(GeneratorService::class)->feed($feed))
        ->toThrow(function (FeedGenerationException $exception) {
            expect($exception->getPrevious())->toBeInstanceOf(LogicException::class);
        });

    expect($feed->storage()->get($feed->storagePath()))->toBe('previous feed');
})->with([
    'get override'     => [LegacyGetOptionsConverterHelper::class, 'get'],
    'resolve override' => [LegacyResolveOptionsConverterHelper::class, 'resolve'],
]);

test('uses an explicit option-aware custom helper hook including inherited hooks', function (string $class) {
    $converter = app($class)->forFeed(app(PartialCsvOptionsFeed::class));
    $item      = new ConverterOptionsItem(new News(['title' => 'Title', 'content' => 'Body']));

    expect($converter)
        ->toBeInstanceOf(BoundOptionsCsvConverter::class)
        ->and($converter->item($item, true))
        ->toBe('Title|Body');
})->with([
    'explicit hook'  => [AwareOptionsConverterHelper::class],
    'inherited hook' => [InheritedAwareOptionsConverterHelper::class],
]);

test('uses one configured converter for all split feed parts', function () {
    $resolutions = 0;

    app()->resolving(CsvConverter::class, function () use (&$resolutions) {
        $resolutions++;
    });

    createNews(
        ['title' => 'First item', 'content' => 'first'],
        ['title' => 'Second item', 'content' => 'second'],
    );

    $feed   = app(SplitCsvOptionsFeed::class);
    $result = app(GeneratorService::class)->feed($feed);

    expect($resolutions)
        ->toBe(1)
        ->and($result->paths)
        ->toBe([$feed->path(1), $feed->path(2)])
        ->and($feed->storage()->get($feed->storagePath(1)))
        ->toBe("'First item',first")
        ->and($feed->storage()->get($feed->storagePath(2)))
        ->toBe("'Second item',second");
});

test('rejects unsupported converter options and dependency overrides', function (FeedFormatEnum $format, array $options, string $class, string $keys) {
    $configuration = config('feeds');
    $feed          = new class (app(), $format, $options) extends ConverterOptionsFeed {
        public function __construct(
            Application $laravel,
            private FeedFormatEnum $selectedFormat,
            private array $options,
        ) {
            parent::__construct($laravel);
        }

        public function format(): FeedFormatEnum
        {
            return $this->selectedFormat;
        }

        public function converterOptions(): array
        {
            return $this->options;
        }
    };

    expect(fn () => app(ConverterHelper::class)->forFeed($feed))
        ->toThrow(InvalidArgumentException::class, "Unknown converter options for [$class]: [$keys].")
        ->and(config('feeds'))
        ->toBe($configuration);
})->with([
    'unknown CSV option'     => [FeedFormatEnum::Csv, ['delimeter' => ','], CsvConverter::class, 'delimeter'],
    'configuration key'      => [FeedFormatEnum::Csv, ['line_ending' => "\n"], CsvConverter::class, 'line_ending'],
    'dependency override'    => [FeedFormatEnum::Csv, ['transformer' => []], CsvConverter::class, 'transformer'],
    'numeric parameter'      => [FeedFormatEnum::Csv, [','], CsvConverter::class, '0'],
    'unsupported JSONL flag' => [FeedFormatEnum::JsonLines, ['pretty' => true], JsonLinesConverter::class, 'pretty'],
    'unsupported XML option' => [FeedFormatEnum::Xml, ['options' => 0], XmlConverter::class, 'options'],
]);
