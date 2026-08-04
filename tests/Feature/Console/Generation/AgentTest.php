<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Commands\FeedGenerateCommand;
use DragonCode\LaravelFeed\Jobs\FeedJob;
use DragonCode\LaravelFeed\Models\Feed;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Symfony\Component\Console\Command\Command;
use Workbench\App\Feeds\SitemapFeed;
use Workbench\App\Feeds\YandexFeed;

test('returns generated files and record counts to an AI agent', function () {
    mockAgent(true);

    config()->set('feeds.console.progress_bar', true);

    $record  = findFeed(SitemapFeed::class);
    $feed    = app($record->class);
    $records = $feed->builder()->count();

    $status = Artisan::call(FeedGenerateCommand::class, [
        'feed' => $record->id,
    ]);

    $output = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

    expect($status)
        ->toBe(Command::SUCCESS)
        ->and($output)
        ->toBe([
            'tool'   => 'feed:generate',
            'result' => 'success',
            'feeds'  => [[
                'class'  => SitemapFeed::class,
                'status' => 'generated',
                'files'  => [[
                    'path'    => $feed->path(),
                    'records' => $records,
                ]],
            ]],
        ]);
});

test('returns queued and skipped feeds to an AI agent', function () {
    mockAgent(true);

    disableFeeds([
        SitemapFeed::class,
        YandexFeed::class,
    ]);

    config()->set([
        'feeds.queue.enabled'    => true,
        'feeds.queue.connection' => 'sync',
        'feeds.queue.name'       => 'feeds',
    ]);

    Bus::fake()->serializeAndRestore();

    $status = Artisan::call(FeedGenerateCommand::class);
    $output = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);
    $feeds  = getAllFeeds();

    expect($status)
        ->toBe(Command::SUCCESS)
        ->and($output)
        ->toBe([
            'tool'   => 'feed:generate',
            'result' => 'success',
            'feeds'  => $feeds
                ->map(static fn (Feed $feed) => [
                    'class'  => $feed->class,
                    'status' => $feed->is_active ? 'queued' : 'skipped',
                ])
                ->values()
                ->all(),
        ]);

    Bus::assertDispatchedSync(FeedJob::class, $feeds->where('is_active', true)->count());
});
