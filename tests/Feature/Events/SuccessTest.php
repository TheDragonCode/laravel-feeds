<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Commands\FeedGenerateCommand;
use DragonCode\LaravelFeed\Data\GenerationResultData;
use DragonCode\LaravelFeed\Events\FeedFinishedEvent;
use DragonCode\LaravelFeed\Events\FeedStartingEvent;
use DragonCode\LaravelFeed\Models\Feed;
use DragonCode\LaravelFeed\Services\GeneratorService;
use Illuminate\Support\Facades\Event;
use Workbench\App\Data\NewsFakeData;
use Workbench\App\Feeds\JsonFeed;
use Workbench\App\Feeds\SplitJsonFeed;

use function Pest\Laravel\artisan;

test('dispatches FeedStarting and FeedFinished events for each generated feed', function () {
    Event::fake();

    artisan(FeedGenerateCommand::class)
        ->assertSuccessful()
        ->run();

    getAllFeeds()->each(function (Feed $feed) {
        Event::assertDispatched(FeedStartingEvent::class, static function (FeedStartingEvent $event) use ($feed) {
            return $event->feed === $feed->class;
        });

        Event::assertDispatched(FeedFinishedEvent::class, static function (FeedFinishedEvent $event) use ($feed) {
            return $event->feed === $feed->class
                && $event->paths !== []
                && $event->path === $event->paths[0]
                && collect($event->paths)->every(static fn (string $path) => is_file($path));
        });
    });
});

test('reports every published path and its record count', function (string $feedClass, array $indexes, array $counts) {
    Event::fake();

    createNews(...NewsFakeData::toArray());

    $feed   = app($feedClass);
    $paths  = array_map(static fn (int $index) => $feed->path($index), $indexes);
    $result = app(GeneratorService::class)->feed($feed);

    expect($result)
        ->toBeInstanceOf(GenerationResultData::class)
        ->and($result->paths)
        ->toBe($paths)
        ->and($result->records)
        ->toBe(array_combine($paths, $counts));

    Event::assertDispatched(FeedFinishedEvent::class, static function (FeedFinishedEvent $event) use ($feedClass, $paths) {
        return $event->feed  === $feedClass
            && $event->path  === $paths[0]
            && $event->paths === $paths;
    });
})->with([
    'single file' => [JsonFeed::class, [0], [3]],
    'split feed'  => [SplitJsonFeed::class, [1, 2], [2, 1]],
]);

test('keeps the legacy finished event path compatible', function () {
    $legacy = new FeedFinishedEvent(JsonFeed::class, 'feed.json');
    $split  = new FeedFinishedEvent(JsonFeed::class, 'feed.json', ['feed-1.json', 'feed-2.json']);

    expect($legacy->path)
        ->toBe('feed.json')
        ->and($legacy->paths)
        ->toBe(['feed.json'])
        ->and($split->path)
        ->toBe('feed-1.json')
        ->and($split->paths)
        ->toBe(['feed-1.json', 'feed-2.json']);
});
