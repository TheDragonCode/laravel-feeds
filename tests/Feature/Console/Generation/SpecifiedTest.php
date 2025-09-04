<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Commands\FeedGenerateCommand;
use DragonCode\LaravelFeed\Models\Feed;
use Workbench\App\Feeds\SitemapFeed;

use function Pest\Laravel\artisan;

test('generate', function () {
    $source = findFeed(SitemapFeed::class);

    $command = artisan(FeedGenerateCommand::class, [
        'feed' => $source->id,
    ]);

    getAllFeeds()->each(
        fn (Feed $feed) => $source->id === $feed->id
            ? $command->expectsOutputToContain($feed->class)
            : $command->doesntExpectOutputToContain($feed->class)
    );

    $command->assertSuccessful()->run();

    getAllFeeds()->each(
        fn (Feed $feed) => $source->id === $feed->id
            ? expect($feed)->toMatchGeneratedFeed()
            : expect($feed)->not->toMatchGeneratedFeed()
    );
});
