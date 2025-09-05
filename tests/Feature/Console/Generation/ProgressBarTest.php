<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Commands\FeedGenerateCommand;
use DragonCode\LaravelFeed\Models\Feed;

use function Pest\Laravel\artisan;

test('enabled', function (bool $enabled) {
    config()?->set('feeds.console.progress_bar', $enabled);

    $command = artisan(FeedGenerateCommand::class);

    getAllFeeds()->each(
        fn (Feed $feed) => $command->expectsOutputToContain($feed->class)
    );

    $command->assertSuccessful()->run();
})->with('boolean');
