<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Services\ExportService;
use DragonCode\LaravelFeed\Services\FilesystemService;
use Illuminate\Console\OutputStyle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\LazyCollection;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

final class InspectableExportService extends ExportService
{
    public function cachedModelCount(): int
    {
        return $this->modelCount();
    }
}

function mockUnboundedExportBuilder(LazyCollection $models, int $chunk): Builder
{
    $builder = mock(Builder::class);
    $query   = mock(QueryBuilder::class);

    $builder->shouldReceive('toBase')->atMost()->once()->andReturn($query);
    $builder->shouldReceive('applyScopes')->once()->andReturnSelf();
    $builder->shouldReceive('withoutGlobalScopes')->once()->andReturnSelf();
    $builder->shouldReceive('getQuery')->once()->andReturn($query);
    $builder->shouldReceive('lazyById')->once()->with($chunk)->andReturn($models);

    return $builder;
}

test('writes each serialized item immediately', function (string $lineEnding) {
    $models = LazyCollection::make(function () {
        foreach (range(1, 3) as $id) {
            $model = mock(Model::class);
            $model->shouldReceive('getKey')->andReturn($id);

            yield $model;
        }
    });

    $builder = mockUnboundedExportBuilder($models, 2);
    $builder->shouldNotReceive('count');

    $feed = mock(Feed::class);
    $feed->shouldReceive('perFile')->once()->andReturn(0);
    $feed->shouldReceive('maxFiles')->once()->andReturn(0);
    $feed->shouldReceive('builder')->once()->andReturn($builder);
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

test('passes item presence to each file creation', function (
    int $modelCount,
    int $perFile,
    int $maxFiles,
    array $expected,
) {
    $models = LazyCollection::make(function () use ($modelCount) {
        for ($id = 1; $id <= $modelCount; $id++) {
            $model = mock(Model::class);
            $model->shouldReceive('getKey')->andReturn($id);

            yield $model;
        }
    });

    $builder = mockUnboundedExportBuilder($models, 2);
    $builder->shouldNotReceive('count');

    $feed = mock(Feed::class);
    $feed->shouldReceive('perFile')->once()->andReturn($perFile);
    $feed->shouldReceive('maxFiles')->once()->andReturn($maxFiles);
    $feed->shouldReceive('builder')->once()->andReturn($builder);

    $filesystem = mock(FilesystemService::class);

    if ($modelCount === 0) {
        $filesystem->shouldNotReceive('append');
    } else {
        $feed->shouldReceive('path')->times($modelCount)->andReturn('feed.json');
        $filesystem->shouldReceive('append')->times($modelCount);
    }

    $presence = [];

    (new ExportService($feed, $filesystem, null))
        ->file(
            create: function (?bool $hasItems = null) use (&$presence) {
                $presence[] = $hasItems;

                return fopen('php://memory', 'wb');
            },
            close: fn ($file) => fclose($file)
        )
        ->item(fn (Model $model) => 'item-' . $model->getKey())
        ->chunk(2)
        ->export();

    expect($presence)->toBe($expected);
})->with([
    'empty file'     => [0, 0, 0, [false]],
    'non-empty file' => [1, 0, 0, [true]],
    'split files'    => [3, 2, 3, [true, true]],
]);

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

    $builder = mockUnboundedExportBuilder($models, 2);
    $builder->shouldNotReceive('count');

    $feed = mock(Feed::class);
    $feed->shouldReceive('perFile')->once()->andReturn(2);
    $feed->shouldReceive('maxFiles')->once()->andReturn(3);
    $feed->shouldReceive('builder')->once()->andReturn($builder);
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
    $feed = mock(Feed::class);
    $feed->shouldReceive('perFile')->once()->andReturn(1);
    $feed->shouldReceive('maxFiles')->once()->andReturn(0);
    $feed->shouldNotReceive('builder');

    $service = new ExportService($feed, mock(FilesystemService::class), null);

    expect(fn () => $service->chunk($chunk))
        ->toThrow(InvalidArgumentException::class);
})->with([0, -1]);

test('closes the active resource when export fails', function () {
    $model = mock(Model::class);

    $builder = mockUnboundedExportBuilder(LazyCollection::make([$model]), 1);
    $builder->shouldNotReceive('count');

    $feed = mock(Feed::class);
    $feed->shouldReceive('perFile')->once()->andReturn(0);
    $feed->shouldReceive('maxFiles')->once()->andReturn(0);
    $feed->shouldReceive('builder')->once()->andReturn($builder);
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

test('creates an empty feed without counting models', function () {
    $builder = mockUnboundedExportBuilder(LazyCollection::make([]), 10);
    $builder->shouldNotReceive('count');

    $feed = mock(Feed::class);
    $feed->shouldReceive('perFile')->once()->andReturn(0);
    $feed->shouldReceive('maxFiles')->once()->andReturn(0);
    $feed->shouldReceive('builder')->once()->andReturn($builder);

    $filesystem = mock(FilesystemService::class);
    $filesystem->shouldNotReceive('append');

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
        ->item(fn () => throw new RuntimeException('No item should be serialized.'))
        ->chunk(10)
        ->export();

    expect($files)
        ->toBe([0])
        ->and($records)
        ->toBe([0]);
});

test('counts models once when progress reporting needs an exact total', function () {
    $models = LazyCollection::make(function () {
        foreach (range(1, 3) as $id) {
            $model = mock(Model::class);
            $model->shouldReceive('getKey')->andReturn($id);

            yield $model;
        }
    });

    $builder = mockUnboundedExportBuilder($models, 2);
    $builder->shouldReceive('count')->once()->andReturn(3);

    $feed = mock(Feed::class);
    $feed->shouldReceive('perFile')->once()->andReturn(0);
    $feed->shouldReceive('maxFiles')->once()->andReturn(0);
    $feed->shouldReceive('builder')->twice()->andReturn($builder);
    $feed->shouldReceive('path')->times(3)->andReturn('feed.jsonl');

    $output = new OutputStyle(new ArrayInput([]), new BufferedOutput);

    $filesystem = mock(FilesystemService::class);
    $filesystem->shouldReceive('append')->times(3);

    (new ExportService($feed, $filesystem, $output))
        ->file(
            create: fn () => fopen('php://memory', 'wb'),
            close : fn ($file) => fclose($file)
        )
        ->item(fn (Model $model) => 'item-' . $model->getKey())
        ->chunk(2)
        ->export();
});

test('stops progress exports at the counted total', function () {
    $models = LazyCollection::make(function () {
        foreach (range(1, 5) as $id) {
            $model = mock(Model::class);
            $model->shouldReceive('getKey')->andReturn($id);

            yield $model;
        }
    });

    $builder = mockUnboundedExportBuilder($models, 2);
    $builder->shouldReceive('count')->once()->andReturn(3);

    $feed = mock(Feed::class);
    $feed->shouldReceive('perFile')->once()->andReturn(0);
    $feed->shouldReceive('maxFiles')->once()->andReturn(0);
    $feed->shouldReceive('builder')->twice()->andReturn($builder);
    $feed->shouldReceive('path')->andReturn('feed.jsonl');

    $output = new OutputStyle(new ArrayInput([]), new BufferedOutput);

    $filesystem = mock(FilesystemService::class);
    $filesystem->shouldReceive('append');

    $items = [];

    (new ExportService($feed, $filesystem, $output))
        ->file(
            create: fn () => fopen('php://memory', 'wb'),
            close : fn ($file) => fclose($file)
        )
        ->item(function (Model $model, bool $isLast) use (&$items) {
            $items[] = [$model->getKey(), $isLast];

            return 'item-' . $model->getKey();
        })
        ->chunk(2)
        ->export();

    expect($items)->toBe([
        [1, false],
        [2, false],
        [3, true],
    ]);
});

test('caps overflowing split capacity at the platform integer limit', function () {
    $feed = mock(Feed::class);
    $feed->shouldReceive('perFile')->once()->andReturn(2);
    $feed->shouldReceive('maxFiles')->once()->andReturn(PHP_INT_MAX);
    $feed->shouldNotReceive('builder');

    expect(new ExportService($feed, mock(FilesystemService::class), null))
        ->toBeInstanceOf(ExportService::class);
});

test('reuses the model count calculated for progress reporting', function () {
    $query              = mock(QueryBuilder::class);
    $query->limit       = null;
    $query->offset      = null;
    $query->unions      = null;
    $query->unionLimit  = null;
    $query->unionOffset = null;

    $builder = mock(Builder::class, [$query])->makePartial();
    $builder->shouldReceive('toBase')->once()->andReturn($query);
    $builder->shouldReceive('count')->once()->andReturn(3);

    $feed = mock(Feed::class);
    $feed->shouldReceive('perFile')->once()->andReturn(0);
    $feed->shouldReceive('maxFiles')->once()->andReturn(0);
    $feed->shouldReceive('builder')->once()->andReturn($builder);

    $output  = new OutputStyle(new ArrayInput([]), new BufferedOutput);
    $service = new InspectableExportService($feed, mock(FilesystemService::class), $output);

    expect($service->cachedModelCount())->toBe(3);
});

test('stops a bounded export when the first page is empty', function () {
    $query              = mock(QueryBuilder::class);
    $query->limit       = 2;
    $query->offset      = 10;
    $query->orders      = null;
    $query->unions      = null;
    $query->unionLimit  = null;
    $query->unionOffset = null;
    $query->unionOrders = null;

    $model = mock(Model::class);
    $model->shouldReceive('getQualifiedKeyName')->once()->andReturn('models.id');

    $builder = mock(Builder::class, [$query])->makePartial();
    $builder->shouldReceive('applyScopes')->once()->andReturnSelf();
    $builder->shouldReceive('withoutGlobalScopes')->once()->andReturnSelf();
    $builder->shouldReceive('getQuery')->once()->andReturn($query);
    $builder->shouldReceive('getModel')->once()->andReturn($model);
    $builder->shouldReceive('orderBy')->once()->with('models.id')->andReturnSelf();
    $builder->shouldReceive('offset')->once()->with(10)->andReturnSelf();
    $builder->shouldReceive('limit')->once()->with(2)->andReturnSelf();
    $builder->shouldReceive('get')->once()->andReturn(new Collection);

    $feed = mock(Feed::class);
    $feed->shouldReceive('perFile')->once()->andReturn(0);
    $feed->shouldReceive('maxFiles')->once()->andReturn(0);
    $feed->shouldReceive('builder')->once()->andReturn($builder);

    $filesystem = mock(FilesystemService::class);
    $filesystem->shouldNotReceive('append');

    (new ExportService($feed, $filesystem, null))
        ->file(
            create: fn () => fopen('php://memory', 'wb'),
            close : fn ($file) => fclose($file)
        )
        ->item(fn () => throw new RuntimeException('No item should be serialized.'))
        ->chunk(2)
        ->export();
});

test('uses an unqualified key for bounded union queries', function () {
    $query              = mock(QueryBuilder::class);
    $query->limit       = null;
    $query->offset      = null;
    $query->orders      = null;
    $query->unions      = [[]];
    $query->unionLimit  = 2;
    $query->unionOffset = 0;
    $query->unionOrders = null;

    $model = mock(Model::class);
    $model->shouldReceive('getKeyName')->once()->andReturn('id');

    $first  = mock(Model::class);
    $second = mock(Model::class);

    $builder = mock(Builder::class, [$query])->makePartial();
    $builder->shouldReceive('applyScopes')->once()->andReturnSelf();
    $builder->shouldReceive('withoutGlobalScopes')->once()->andReturnSelf();
    $builder->shouldReceive('getQuery')->once()->andReturn($query);
    $builder->shouldReceive('getModel')->once()->andReturn($model);
    $builder->shouldReceive('orderBy')->once()->with('id')->andReturnSelf();
    $builder->shouldReceive('offset')->once()->with(0)->andReturnSelf();
    $builder->shouldReceive('limit')->once()->with(2)->andReturnSelf();
    $builder->shouldReceive('get')->once()->andReturn(new Collection([$first, $second]));

    $feed = mock(Feed::class);
    $feed->shouldReceive('perFile')->once()->andReturn(0);
    $feed->shouldReceive('maxFiles')->once()->andReturn(0);
    $feed->shouldReceive('builder')->once()->andReturn($builder);
    $feed->shouldReceive('path')->twice()->andReturn('feed.json');

    $filesystem = mock(FilesystemService::class);
    $filesystem->shouldReceive('append')->twice();

    (new ExportService($feed, $filesystem, null))
        ->file(
            create: fn () => fopen('php://memory', 'wb'),
            close : fn ($file) => fclose($file)
        )
        ->item(fn () => 'item')
        ->chunk(2)
        ->export();
});

test('stops a bounded export after a partial final page', function () {
    $query              = mock(QueryBuilder::class);
    $query->limit       = 2;
    $query->offset      = 0;
    $query->orders      = ['id'];
    $query->unions      = null;
    $query->unionLimit  = null;
    $query->unionOffset = null;
    $query->unionOrders = null;

    $model = mock(Model::class);

    $builder = mock(Builder::class, [$query])->makePartial();
    $builder->shouldReceive('applyScopes')->once()->andReturnSelf();
    $builder->shouldReceive('withoutGlobalScopes')->once()->andReturnSelf();
    $builder->shouldReceive('getQuery')->once()->andReturn($query);
    $builder->shouldReceive('offset')->once()->with(0)->andReturnSelf();
    $builder->shouldReceive('limit')->once()->with(2)->andReturnSelf();
    $builder->shouldReceive('get')->once()->andReturn(new Collection([$model]));

    $feed = mock(Feed::class);
    $feed->shouldReceive('perFile')->once()->andReturn(0);
    $feed->shouldReceive('maxFiles')->once()->andReturn(0);
    $feed->shouldReceive('builder')->once()->andReturn($builder);
    $feed->shouldReceive('path')->once()->andReturn('feed.json');

    $filesystem = mock(FilesystemService::class);
    $filesystem->shouldReceive('append')->once();

    (new ExportService($feed, $filesystem, null))
        ->file(
            create: fn () => fopen('php://memory', 'wb'),
            close : fn ($file) => fclose($file)
        )
        ->item(fn () => 'item')
        ->chunk(2)
        ->export();
});
