<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Console\Commands\FeedGenerateCommand;
use DragonCode\LaravelFeed\Models\Feed;
use Workbench\App\Feeds\SitemapFeed;

use function Pest\Laravel\artisan;

test('generate', function () {
    $source = findFeed(SitemapFeed::class);

    $command = artisan(FeedGenerateCommand::class, [
        'feed' => SitemapFeed::class,
    ]);

    getAllFeeds()->each(
        fn (Feed $feed) => $source->id === $feed->id
            ? $command->expectsOutputToContain($feed->class)
            : $command->doesntExpectOutputToContain($feed->class)
    );

    $command->assertSuccessful()->run();

    getAllFeeds()->each(
        fn (Feed $feed) => $source->id === $feed->id
            ? expect(app($feed->class)->path())->toBeReadableFile()
            : expect(app($feed->class)->path())->not->toBeReadableFile()
    );
});
