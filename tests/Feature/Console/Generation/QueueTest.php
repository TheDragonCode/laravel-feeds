<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Commands\FeedGenerateCommand;
use DragonCode\LaravelFeed\Jobs\FeedJob;
use DragonCode\LaravelFeed\Models\Feed;
use Illuminate\Support\Facades\Queue;
use Workbench\App\Feeds\FullFeed;
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

    artisan(FeedGenerateCommand::class, ['--ansi' => true])
        ->expectsOutputToContain("\e[32mQUEUED\e[39m")
        ->expectsOutputToContain("\e[33mSKIP\e[39m")
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

    artisan(FeedGenerateCommand::class, ['--no-ansi' => true])
        ->expectsOutputToContain('QUEUED')
        ->expectsOutputToContain('SKIP')
        ->doesntExpectOutputToContain("\e[")
        ->doesntExpectOutputToContain('[32mQUEUED[39m')
        ->doesntExpectOutputToContain('[33mSKIP[39m')
        ->doesntExpectOutputToContain('<fg=')
        ->assertSuccessful()
        ->run();
});

test('does not resolve an ordinary feed before queueing', function () {
    config()->set([
        'feeds.queue.enabled'    => true,
        'feeds.queue.connection' => 'sync',
        'feeds.queue.name'       => 'feeds',
    ]);

    app()->bind(FullFeed::class, static fn () => throw new RuntimeException('Ordinary feed was resolved.'));

    Queue::fake()->serializeAndRestore();

    $feed = findFeed(FullFeed::class);

    artisan(FeedGenerateCommand::class, [
        'feed' => (string) $feed->id,
    ])->assertSuccessful()->run();

    Queue::assertPushed(
        FeedJob::class,
        static fn (FeedJob $job) => $job->feedClass === FullFeed::class && $job->target === null,
    );
});
