<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Console\Commands\FeedGenerateCommand;
use Workbench\App\Feeds\SitemapFeed;
use Workbench\App\Feeds\YandexFeed;

use function Pest\Laravel\artisan;

test('generate', function () {
    config()?->set('feeds.channels.' . SitemapFeed::class, false);
    config()?->set('feeds.channels.' . YandexFeed::class, false);

    $command = artisan(FeedGenerateCommand::class);

    config()
        ?->collection('feeds.channels')
        ?->keys()
        ?->each(fn (string $feed) => $command->expectsOutputToContain($feed));

    $command->assertSuccessful()->run();

    config()
        ?->collection('feeds.channels')
        ?->keys()
        ?->each(fn (string $feed) => match ($feed) {
            SitemapFeed::class,
            YandexFeed::class => expect(app($feed)->path())->not->toBeReadableFile(),
            default           => expect(app($feed)->path())->toBeReadableFile()
        });
});
