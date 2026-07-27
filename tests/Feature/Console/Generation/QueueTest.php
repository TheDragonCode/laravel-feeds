<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Commands\FeedGenerateCommand;
use DragonCode\LaravelFeed\Jobs\FeedJob;
use DragonCode\LaravelFeed\Models\Feed;
use Illuminate\Support\Facades\Queue;
use Workbench\App\Feeds\SitemapFeed;
use Workbench\App\Feeds\YandexFeed;

use function Pest\Laravel\artisan;

test('queue', function () {
    disableFeeds([
        SitemapFeed::class,
        YandexFeed::class,
    ]);

    config()->set([
        'feeds.queue.enabled'    => true,
        'feeds.queue.connection' => 'sync',
        'feeds.queue.name'       => 'feeds',
    ]);

    Queue::fake()->serializeAndRestore();

    artisan(FeedGenerateCommand::class)
        ->expectsOutputToContain('QUEUED')
        ->expectsOutputToContain('SKIP')
        ->assertSuccessful()
        ->run();

    $active = getAllFeeds()->where('is_active', true);

    Queue::assertPushed(FeedJob::class, $active->count());

    $active->each(
        fn (Feed $feed) => Queue::assertPushed(
            FeedJob::class,
            fn (FeedJob $job) => $job->feedClass === $feed->class
                && $job->connection              === 'sync'
                && $job->queue                   === 'feeds'
        )
    );

    Queue::assertNotPushed(
        FeedJob::class,
        fn (FeedJob $job) => in_array($job->feedClass, [SitemapFeed::class, YandexFeed::class], true)
    );
});
