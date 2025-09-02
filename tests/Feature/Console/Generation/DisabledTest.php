<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Console\Commands\FeedGenerateCommand;
use DragonCode\LaravelFeed\Models\Feed;
use Workbench\App\Feeds\SitemapFeed;
use Workbench\App\Feeds\YandexFeed;

use function Pest\Laravel\artisan;

test('generate', function () {
    disableFeeds([
        SitemapFeed::class,
        YandexFeed::class,
    ]);

    $command = artisan(FeedGenerateCommand::class);

    getAllFeeds()->each(
        fn (Feed $feed) => $command->expectsOutputToContain($feed->class)
    );

    $command->assertSuccessful()->run();

    getAllFeeds()->each(
        fn (Feed $feed) => match ($feed->class) {
            SitemapFeed::class,
            YandexFeed::class => expect(app($feed->class)->path())->not->toBeReadableFile(),
            default           => expect(app($feed->class)->path())->toBeReadableFile()
        }
    );
});
