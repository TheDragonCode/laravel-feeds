<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Models\Feed;
use DragonCode\LaravelFeed\Queries\FeedQuery;
use DragonCode\LaravelFeed\Scheduling\Expression;
use Workbench\App\Feeds\EmptyFeed;

test('creating', function () {
    Feed::query()->forceDelete();

    $feed = app(FeedQuery::class)->create(
        class     : EmptyFeed::class,
        title     : 'Some',
        expression: '*/15 */2 * 1 *'
    );

    expect($feed)
        ->class->toBe(EmptyFeed::class)
        ->title->toBe('Some')
        ->expression->toBe('*/15 */2 * 1 *')
        ->is_active->toBeTrue()
        ->last_activity->toBeNull();
});

test('creating with fluent expression', function () {
    Feed::query()->forceDelete();

    $feed = app(FeedQuery::class)->create(
        class     : EmptyFeed::class,
        title     : 'Some',
        expression: (new Expression)
            ->weekly()
            ->mondays()
            ->at('13:00')
    );

    expect($feed->expression)->toBe('0 13 * * 1')
        ->and($feed->getRawOriginal('expression'))->toBe('0 13 * * 1');
});
