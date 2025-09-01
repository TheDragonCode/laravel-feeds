<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Console\Commands\FeedGenerateCommand;
use Workbench\App\Feeds\SitemapFeed;

use function Pest\Laravel\artisan;

test('generate', function () {
    $command = artisan(FeedGenerateCommand::class, [
        'class' => SitemapFeed::class,
    ]);

    config()
        ?->collection('feeds.channels')
        ?->keys()
        ?->each(
            fn (string $feed) => $feed === SitemapFeed::class
            ? $command->expectsOutputToContain($feed)
            : $command->doesntExpectOutputToContain($feed)
        );

    $command->assertSuccessful()->run();

    config()
        ?->collection('feeds.channels')
        ?->keys()
        ?->each(
            fn (string $feed) => $feed === SitemapFeed::class
            ? expect(app($feed)->path())->toBeReadableFile()
            : expect(app($feed)->path())->not->toBeReadableFile()
        );
});
