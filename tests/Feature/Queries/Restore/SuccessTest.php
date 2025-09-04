<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Models\Feed;
use DragonCode\LaravelFeed\Queries\FeedQuery;

use function Pest\Laravel\assertDatabaseHas;

test('success', function () {
    $feed = Feed::firstOrFail();
    $feed->delete();

    assertDatabaseHas(Feed::class, [
        'id'         => $feed->id,
        'deleted_at' => $feed->deleted_at->toDateTimeString(),
    ]);

    app(FeedQuery::class)->restore($feed->id);

    assertDatabaseHas(Feed::class, [
        'id'         => $feed->id,
        'deleted_at' => null,
    ]);
});
