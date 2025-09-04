<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Models\Feed;
use DragonCode\LaravelFeed\Queries\FeedQuery;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

test('success', function () {
    $feed = Feed::firstOrFail();

    assertDatabaseHas(Feed::class, [
        'id'         => $feed->id,
        'deleted_at' => null,
    ]);

    app(FeedQuery::class)->delete($feed->id);

    assertDatabaseHas(Feed::class, [
        'id' => $feed->id,
    ]);

    assertDatabaseMissing(Feed::class, [
        'id'         => $feed->id,
        'deleted_at' => null,
    ]);
});
