<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Commands\FeedGenerateCommand;
use DragonCode\LaravelFeed\Models\Feed;

use function Pest\Laravel\artisan;

test('generate', function () {
    $command = artisan(FeedGenerateCommand::class);

    getAllFeeds()->each(
        fn (Feed $feed) => $command->expectsOutputToContain($feed->class)
    );

    $command->assertSuccessful()->run();

    getAllFeeds()->each(
        fn (Feed $feed) => expect($feed)->toMatchGeneratedFeed()
    );
});
