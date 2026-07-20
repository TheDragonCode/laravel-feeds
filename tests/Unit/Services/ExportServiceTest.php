<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Services\ExportService;
use DragonCode\LaravelFeed\Services\FilesystemService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\LazyCollection;

test('writes each serialized item immediately', function () {
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
        ->chunk(2)
        ->export();

    expect($writes)->toBe([
        'item-1',
        PHP_EOL . 'item-2',
        PHP_EOL . 'item-3',
    ]);
});
