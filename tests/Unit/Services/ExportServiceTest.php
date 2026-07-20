<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Services\ExportService;
use DragonCode\LaravelFeed\Services\FilesystemService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\LazyCollection;

test('writes each serialized item immediately', function (string $lineEnding) {
    $models = LazyCollection::make(function () {
        foreach (range(1, 3) as $id) {
            $model = mock(Model::class);
            $model->shouldReceive('getKey')->andReturn($id);

            yield $model;
        }
    });

    $builder = mock(Builder::class);
    $builder->shouldReceive('count')->once()->andReturn(3);
    $builder->shouldReceive('lazyById')->once()->with(2)->andReturn($models);

    $feed = mock(Feed::class);
    $feed->shouldReceive('perFile')->once()->andReturn(0);
    $feed->shouldReceive('maxFiles')->once()->andReturn(0);
    $feed->shouldReceive('builder')->twice()->andReturn($builder);
    $feed->shouldReceive('path')->times(3)->andReturn('feed.xml');

    $writes = [];

    $filesystem = mock(FilesystemService::class);
    $filesystem->shouldReceive('append')
        ->times(3)
        ->andReturnUsing(function ($resource, string $content) use (&$writes) {
            $writes[] = $content;
        });

    $resource = fopen('php://memory', 'wb');

    (new ExportService($feed, $filesystem, null))
        ->file(
            create: fn () => $resource,
            close : fn ($file) => fclose($file)
        )
        ->item(fn (Model $model) => 'item-' . $model->getKey())
        ->lineEnding($lineEnding)
        ->chunk(2)
        ->export();

    expect($writes)->toBe([
        'item-1',
        $lineEnding . 'item-2',
        $lineEnding . 'item-3',
    ]);
})->with([PHP_EOL, '<EOL>']);

test('respects split capacity', function (
    int $modelCount,
    array $expectedItems,
    array $expectedFiles,
    array $expectedRecords,
) {
    $models = LazyCollection::make(function () use ($modelCount) {
        foreach (range(1, $modelCount) as $id) {
            $model = mock(Model::class);
            $model->shouldReceive('getKey')->andReturn($id);

            yield $model;
        }
    });

    $builder = mock(Builder::class);
    $builder->shouldReceive('count')->once()->andReturn($modelCount);
    $builder->shouldReceive('lazyById')->once()->with(2)->andReturn($models);

    $feed = mock(Feed::class);
    $feed->shouldReceive('perFile')->once()->andReturn(2);
    $feed->shouldReceive('maxFiles')->once()->andReturn(3);
    $feed->shouldReceive('builder')->twice()->andReturn($builder);
    $feed->shouldReceive('path')->times(count($expectedItems))->andReturn('feed.json');

    $filesystem = mock(FilesystemService::class);
    $filesystem->shouldReceive('append')->times(count($expectedItems));

    $items   = [];
    $files   = [];
    $records = [];

    (new ExportService($feed, $filesystem, null))
        ->file(
            create: fn () => fopen('php://memory', 'wb'),
            close : function ($file, int $index, int $count) use (&$files, &$records) {
                $files[]   = $index;
                $records[] = $count;

                fclose($file);
            }
        )
        ->item(function (Model $model, bool $isLast) use (&$items) {
            $items[] = [$model->getKey(), $isLast];

            return 'item-' . $model->getKey();
        })
        ->chunk(2)
        ->export();

    expect($items)
        ->toBe($expectedItems)
        ->and($files)
        ->toBe($expectedFiles)
        ->and($records)
        ->toBe($expectedRecords);
})->with([
    'single file' => [
        2,
        [[1, false], [2, true]],
        [0],
        [2],
    ],
    'partial final file' => [
        3,
        [[1, false], [2, true], [3, true]],
        [1, 2],
        [2, 1],
    ],
    'exact capacity' => [
        6,
        [[1, false], [2, true], [3, false], [4, true], [5, false], [6, true]],
        [1, 2, 3],
        [2, 2, 2],
    ],
    'over capacity' => [
        10,
        [[1, false], [2, true], [3, false], [4, true], [5, false], [6, true]],
        [1, 2, 3],
        [2, 2, 2],
    ],
]);

test('rejects a negative per-file limit', function () {
    $feed = mock(Feed::class);
    $feed->shouldReceive('perFile')->once()->andReturn(-1);

    expect(fn () => new ExportService($feed, mock(FilesystemService::class), null))
        ->toThrow(InvalidArgumentException::class);
});

test('rejects a negative file limit', function () {
    $feed = mock(Feed::class);
    $feed->shouldReceive('perFile')->once()->andReturn(1);
    $feed->shouldReceive('maxFiles')->once()->andReturn(-1);

    expect(fn () => new ExportService($feed, mock(FilesystemService::class), null))
        ->toThrow(InvalidArgumentException::class);
});

test('rejects a non-positive chunk size', function (int $chunk) {
    $builder = mock(Builder::class);
    $builder->shouldReceive('count')->once()->andReturn(1);

    $feed = mock(Feed::class);
    $feed->shouldReceive('perFile')->once()->andReturn(1);
    $feed->shouldReceive('maxFiles')->once()->andReturn(0);
    $feed->shouldReceive('builder')->once()->andReturn($builder);

    $service = new ExportService($feed, mock(FilesystemService::class), null);

    expect(fn () => $service->chunk($chunk))
        ->toThrow(InvalidArgumentException::class);
})->with([0, -1]);

test('closes the active resource when export fails', function () {
    $model = mock(Model::class);

    $builder = mock(Builder::class);
    $builder->shouldReceive('count')->once()->andReturn(1);
    $builder->shouldReceive('lazyById')->once()->with(1)->andReturn(LazyCollection::make([$model]));

    $feed = mock(Feed::class);
    $feed->shouldReceive('perFile')->once()->andReturn(0);
    $feed->shouldReceive('maxFiles')->once()->andReturn(0);
    $feed->shouldReceive('builder')->twice()->andReturn($builder);
    $feed->shouldReceive('path')->once()->andReturn('feed.json');

    $resource = fopen('php://memory', 'wb');

    $filesystem = mock(FilesystemService::class);
    $filesystem->shouldReceive('append')->once()->andThrow(new RuntimeException('Write failed.'));
    $filesystem->shouldReceive('close')->once()->with($resource)->andReturnUsing(fclose(...));

    $service = (new ExportService($feed, $filesystem, null))
        ->file(
            create: fn () => $resource,
            close : fn () => null
        )
        ->item(fn () => 'item')
        ->chunk(1);

    expect(fn () => $service->export())
        ->toThrow(RuntimeException::class, 'Write failed.')
        ->and(is_resource($resource))
        ->toBeFalse();
});
