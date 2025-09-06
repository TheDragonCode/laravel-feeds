<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Feeds\Feed;

test('filename', function (string $class) {
    /** @var Feed $feed */
    $feed = app($class);

    expect($feed->filename())->toMatchSnapshot();
})->with('feed classes');
