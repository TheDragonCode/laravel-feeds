<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Commands\FeedGenerateCommand;
use DragonCode\LaravelFeed\Exceptions\FeedGenerationException;
use DragonCode\LaravelFeed\Jobs\FeedJob;
use DragonCode\LaravelFeed\Models\Feed;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Symfony\Component\Console\Command\Command;
use Workbench\App\Data\NewsFakeData;
use Workbench\App\Feeds\FailedFeed;
use Workbench\App\Feeds\SitemapFeed;
use Workbench\App\Feeds\SplitPerFileFeed;
use Workbench\App\Feeds\YandexFeed;

test('returns generated files and record counts to an AI agent', function () {
    mockAgent(true);

    config()->set('feeds.console.progress_bar', true);

    createNews(...NewsFakeData::toArray());

    $record = findFeed(SplitPerFileFeed::class);
    $feed   = app($record->class);

    $status = Artisan::call(FeedGenerateCommand::class, [
        'feed' => $record->id,
    ]);

    $raw      = Artisan::output();
    $output   = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
    $expected = [
        'tool'   => 'feed:generate',
        'result' => 'success',
        'feeds'  => [[
            'class'  => SplitPerFileFeed::class,
            'status' => 'generated',
            'files'  => [
                [
                    'path'    => $feed->path(1),
                    'records' => 2,
                ],
                [
                    'path'    => $feed->path(2),
                    'records' => 1,
                ],
            ],
        ]],
    ];

    expect($status)
        ->toBe(Command::SUCCESS)
        ->and($output)
        ->toBe($expected)
        ->and($raw)
        ->toBe(json_encode(
            $expected,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
        ) . PHP_EOL);
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

    $feeds->where('is_active', true)->each(
        fn (Feed $feed) => Bus::assertDispatchedSync(
            FeedJob::class,
            fn (FeedJob $job) => $job->feedClass === $feed->class
        )
    );
});

test('does not emit a success response when generation fails', function () {
    mockAgent(true);

    $feed = Feed::create([
        'class' => FailedFeed::class,
        'title' => 'Failed',
    ]);

    try {
        Artisan::call(FeedGenerateCommand::class, ['feed' => $feed->id]);
    } catch (FeedGenerationException $exception) {
        expect(Artisan::output())->not->toContain('"result":"success"');

        throw $exception;
    }
})->throws(FeedGenerationException::class, 'Something went wrong while generating the feed.');
