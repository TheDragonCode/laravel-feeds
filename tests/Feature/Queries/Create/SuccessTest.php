<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Models\Feed;
use DragonCode\LaravelFeed\Queries\FeedQuery;
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
