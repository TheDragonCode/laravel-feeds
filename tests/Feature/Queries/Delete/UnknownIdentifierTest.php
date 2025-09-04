<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Models\Feed;
use DragonCode\LaravelFeed\Queries\FeedQuery;

use function Pest\Laravel\assertDatabaseMissing;

test('failed', function () {
    app(FeedQuery::class)->delete(1000);

    assertDatabaseMissing(Feed::class, [
        'id' => 1000,
    ]);
});
