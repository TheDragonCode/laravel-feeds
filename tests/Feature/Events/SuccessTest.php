<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Commands\FeedGenerateCommand;
use DragonCode\LaravelFeed\Events\FeedFinishedEvent;
use DragonCode\LaravelFeed\Events\FeedStartingEvent;
use DragonCode\LaravelFeed\Models\Feed;
use Illuminate\Support\Facades\Event;

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
                && $event->path === app($feed->class)->path();
        });
    });
});
