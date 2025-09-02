<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Helpers;

use DragonCode\LaravelFeed\Console\Commands\FeedGenerateCommand;
use DragonCode\LaravelFeed\Models\Feed;
use DragonCode\LaravelFeed\Queries\FeedQuery;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Container\Attributes\Config;
use Illuminate\Support\Facades\Schedule;

class ScheduleFeedHelper
{
    public function __construct(
        protected FeedQuery $query,
        #[Config('feeds.schedule.background')]
        protected bool $canBackground,
        #[Config('feeds.schedule.ttl')]
        protected int $ttl,
    ) {}

    public function commands(): void
    {
        $this->query->active()->each(
            fn (Feed $feed) => $this->register($feed)
        );
    }

    protected function register(Feed $feed): void
    {
        $event = $this->event($feed);

        if ($this->canBackground) {
            $event->runInBackground();
        }
    }

    protected function event(Feed $feed): Event
    {
        return Schedule::command(FeedGenerateCommand::class, [$feed->id])
            ->withoutOverlapping($this->ttl)
            ->cron($feed->expression);
    }
}
