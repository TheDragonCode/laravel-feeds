<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Models\Feed;
use DragonCode\LaravelFeed\Queries\FeedQuery;
use Workbench\App\Feeds\EmptyFeed;
use Workbench\App\Feeds\FullFeed;
use Workbench\App\Feeds\ModelFeed;

test('lists feeds in ID order with optional deleted records', function (array $arguments, array $indices) {
    Feed::withTrashed()->forceDelete();

    $feeds = [
        Feed::create(['class' => EmptyFeed::class, 'title' => 'Zulu', 'is_active' => false]),
        Feed::create(['class' => FullFeed::class, 'title' => 'Alpha']),
        Feed::create(['class' => ModelFeed::class, 'title' => 'Bravo']),
    ];

    $feeds[1]->delete();

    expect(app(FeedQuery::class)->all(...$arguments)->pluck('id')->all())->toBe(
        array_map(fn (int $index) => $feeds[$index]->id, $indices)
    );
})->with([
    'without deleted records by default' => [[], [0, 2]],
    'with deleted records'               => [[true], [0, 1, 2]],
]);
