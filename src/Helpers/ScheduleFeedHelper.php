<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Helpers;

use DragonCode\LaravelFeed\Commands\FeedGenerateCommand;
use DragonCode\LaravelFeed\Models\Feed;
use DragonCode\LaravelFeed\Queries\FeedQuery;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Container\Attributes\Config;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;

use function app;

class ScheduleFeedHelper
{
    public function __construct(
        protected FeedQuery $query,
        #[Config('feeds.schedule.background')]
        protected bool $canBackground,
        #[Config('feeds.schedule.ttl')]
        protected int $ttl,
        #[Config('feeds.table.connection')]
        protected ?string $connection,
        #[Config('feeds.table.table')]
        protected string $table,
    ) {}

    public static function register(): void
    {
        app(static::class)->commands();
    }

    public function commands(): void
    {
        if (! $this->canRegister()) {
            return;
        }

        $this->query->active()->each(
            fn (Feed $feed) => $this->schedule($feed)
        );
    }

    protected function schedule(Feed $feed): void
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

    protected function canRegister(): bool
    {
        return Schema::connection($this->connection)->hasTable($this->table);
    }
}
