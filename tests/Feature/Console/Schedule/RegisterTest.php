<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Helpers\ScheduleFeedHelper;
use DragonCode\LaravelFeed\Models\Feed;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Str;

test('cron', function () {
    expect(Schedule::events())->toHaveCount(0);

    Feed::firstOrFail()->update(['title' => 'Renamed scheduled feed']);

    ScheduleFeedHelper::register();

    $feeds = Feed::get();

    $events = collect(Schedule::events())
        ->mapWithKeys(function (Event $event) {
            $key   = Str::of($event->command)->afterLast(' ')->trim("'\"")->toString();
            $value = $event->expression;

            return [$key => $value];
        })
        ->all();

    expect($events)->toHaveCount(
        $feeds->count()
    );

    $feeds->each(
        fn (Feed $feed) => expect($events[$feed->slug])->toBe($feed->expression)
    );
});
