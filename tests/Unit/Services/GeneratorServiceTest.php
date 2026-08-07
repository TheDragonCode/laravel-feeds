<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Converters\Converter;
use DragonCode\LaravelFeed\Enums\FeedFormatEnum;
use DragonCode\LaravelFeed\Exceptions\FeedGenerationException;
use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Helpers\ConverterHelper;
use DragonCode\LaravelFeed\Queries\FeedQuery;
use DragonCode\LaravelFeed\Services\FilesystemService;
use DragonCode\LaravelFeed\Services\GeneratorService;
use DragonCode\LaravelFeed\Transformers\DateTimeTransformer;
use Illuminate\Filesystem\FilesystemAdapter;

test('rejects publication that finishes without producing a generation result', function () {
    $storage = mock(FilesystemAdapter::class);

    $feed = mock(Feed::class);
    $feed->shouldReceive('storage')->once()->andReturn($storage);
    $feed->shouldReceive('path')->once()->andReturn('feed.json');
    $feed->shouldReceive('storagePath')->once()->andReturn('feed.json');
    $feed->shouldReceive('format')->once()->andReturn(FeedFormatEnum::Json);
    $feed->shouldReceive('transformers')->once()->andReturn([DateTimeTransformer::class]);

    $filesystem = mock(FilesystemService::class);
    $filesystem->shouldReceive('publishTo')
        ->once()
        ->with($storage, 'feed.json', Mockery::type(Closure::class));

    $converter = mock(Converter::class);

    $helper = mock(ConverterHelper::class);
    $helper->shouldReceive('get')
        ->once()
        ->with(FeedFormatEnum::Json, [DateTimeTransformer::class])
        ->andReturn($converter);

    $query = mock(FeedQuery::class);
    $query->shouldNotReceive('setLastActivity');

    expect(fn () => (new GeneratorService($filesystem, $helper, $query))->feed($feed))
        ->toThrow(FeedGenerationException::class, 'Feed generation did not produce a result.');
});

test('preserves subclass properties named target', function () {
    $exception = new class (Feed::class, new RuntimeException('Failed.'), '42') extends FeedGenerationException {
        protected string $target = 'custom';

        public function customTarget(): string
        {
            return $this->target;
        }
    };

    expect($exception->customTarget())
        ->toBe('custom')
        ->and($exception->getTarget())
        ->toBe('42');
});

test('serialized legacy exceptions keep a null target', function () {
    $class     = FeedGenerationException::class;
    $exception = unserialize(sprintf('O:%d:"%s":0:{}', strlen($class), $class));

    expect($exception)
        ->toBeInstanceOf(FeedGenerationException::class)
        ->and($exception->getTarget())
        ->toBeNull();
});
