<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Commands\FeedGenerateCommand;
use DragonCode\LaravelFeed\Models\Feed;

use function Pest\Laravel\artisan;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

test('default', function () {
    $feed = Feed::firstOrFail();

    assertDatabaseHas(Feed::class, [
        'id' => $feed->id,

        'last_activity' => null,
    ]);

    artisan(FeedGenerateCommand::class)
        ->expectsOutputToContain($feed->class)
        ->assertSuccessful()
        ->run();

    assertDatabaseMissing(Feed::class, [
        'id' => $feed->id,

        'last_activity' => null,
    ]);

    $feed->refresh();

    expect($feed->last_activity)->toDateTime();
});
