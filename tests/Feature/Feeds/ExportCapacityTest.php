<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Services\ExportService;
use DragonCode\LaravelFeed\Services\FilesystemService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Workbench\App\Models\News;

final class ExportCapacityNews extends News
{
    protected $table = 'news';

    public function related(): HasOne
    {
        return $this->hasOne(News::class, 'id', 'id');
    }
}

test('fetches models in batches bounded by export capacity', function (
    string $queryType,
    int $chunk,
    int $perFile,
    int $maxFiles,
    array $expectedIds,
    array $expectedBatches,
) {
    News::query()->insert(array_map(fn (int $id) => [
        'id'       => $id,
        'title'    => 'News ' . $id,
        'content'  => 'Content ' . $id,
        'category' => 'news',
    ], range(1, 1000)));

    $eagerBatches = [];

    $query = match ($queryType) {
        'ordered' => News::query()->orderByDesc('id'),
        'limit'   => News::query()->orderBy('id')->limit(100),
        'offset'  => News::query()->orderBy('id')->offset(50)->limit(100),
        'scoped'  => News::query()->withGlobalScope('visible', fn (Builder $builder) => $builder->where('id', '>', 100)),
        'eager'   => ExportCapacityNews::query()->with([
            'related' => function (HasOne $relation) use (&$eagerBatches) {
                $relation->getQuery()->afterQuery(function (Collection $models) use (&$eagerBatches) {
                    $eagerBatches[] = $models->count();
                });
            },
        ]),
        'union' => News::query()->where('id', '<=', 500)
            ->unionAll(News::query()->where('id', '>', 500))
            ->offset(50)->limit(100),
        default => News::query(),
    };

    $batches = [];

    $query->afterQuery(function (Collection $models) use (&$batches) {
        $batches[] = $models->count();
    });

    $feed = mock(Feed::class);
    $feed->shouldReceive('perFile')->once()->andReturn($perFile);
    $feed->shouldReceive('maxFiles')->once()->andReturn($maxFiles);
    $feed->shouldReceive('builder')->once()->andReturn($query);
    $feed->shouldReceive('path')->andReturn('feed.txt');
    $feed->shouldReceive('chunkSize')->once()->andReturn($chunk);

    $files = [];

    new ExportService($feed, app(FilesystemService::class), null)
        ->file(
            create: fn () => fopen('php://memory', 'w+b'),
            close : function ($file) use (&$files) {
                rewind($file);

                $files[] = array_map('intval', explode("\n", stream_get_contents($file)));

                fclose($file);
            }
        )
        ->item(fn (Model $model) => (string) $model->getKey())
        ->lineEnding("\n")
        ->chunk($feed->chunkSize())
        ->export();

    expect(array_merge(...$files))->toBe($expectedIds)
        ->and($batches)->toBe($expectedBatches)
        ->and($eagerBatches)->toBe($queryType === 'eager' ? $expectedBatches : []);
})->with([
    'unordered capacity' => ['unordered', 1000, 10, 1, range(1, 10), [10]],
    'ordered capacity'   => ['ordered', 1000, 10, 1, range(1000, 991), [10]],
    'query limit'        => ['limit', 1000, 10, 1, range(1, 10), [10]],
    'query offset'       => ['offset', 1000, 10, 1, range(51, 60), [10]],
    'scoped capacity'    => ['scoped', 1000, 10, 1, range(101, 110), [10]],
    'eager loaded'       => ['eager', 1000, 10, 1, range(1, 10), [10]],
    'bounded union'      => ['union', 1000, 10, 1, range(51, 60), [10]],
    'batch boundary'     => ['unordered', 5, 5, 2, range(1, 10), [5, 5]],
    'unlimited records'  => ['unordered', 1000, 0, 1, range(1, 1000), [1000, 0]],
    'unlimited files'    => ['unordered', 1000, 10, 0, range(1, 1000), [1000, 0]],
]);
