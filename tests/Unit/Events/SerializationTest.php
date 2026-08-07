<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Events\FeedFinishedEvent;
use DragonCode\LaravelFeed\Events\FeedStartingEvent;
use Workbench\App\Feeds\JsonFeed;

test('serialized legacy events keep a null target', function () {
    $starting = new FeedStartingEvent(JsonFeed::class);
    $finished = new FeedFinishedEvent(JsonFeed::class, 'feed.json');

    unset($starting->target, $finished->target);

    $starting = unserialize(serialize($starting));
    $finished = unserialize(serialize($finished));

    expect($starting->target)
        ->toBeNull()
        ->and($finished->target)
        ->toBeNull();
});
