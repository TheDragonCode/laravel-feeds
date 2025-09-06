<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Commands\FeedGenerateCommand;
use DragonCode\LaravelFeed\Models\Feed;

use function Pest\Laravel\artisan;

test('generates only the selected feed by ID', function (int $id) {
    $command = artisan(FeedGenerateCommand::class, [
        'feed' => $id,
    ]);

    getAllFeeds()->each(
        fn (Feed $feed) => $id === $feed->id
            ? $command->expectsOutputToContain($feed->class)
            : $command->doesntExpectOutputToContain($feed->class)
    );

    $command->assertSuccessful()->run();

    getAllFeeds()->each(
        fn (Feed $feed) => $id === $feed->id
            ? expect($feed)->toMatchGeneratedFeed()
            : expect($feed)->not->toMatchGeneratedFeed()
    );
})->with('generation ids');
