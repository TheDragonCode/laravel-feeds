<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Jobs;

use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Services\GeneratorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use function config;

final class FeedJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Feed $feed
    ) {
        $this->onConnection(config('feeds.queue.connection'));
        $this->onQueue(config('feeds.queue.name'));
        $this->onGroup(config('feeds.queue.group'));
    }

    public function handle(GeneratorService $generator): void
    {
        $generator->feed($this->feed);
    }

    public function uniqueId(): string
    {
        return $this->feed->filename();
    }

    public function uniqueFor(): int
    {
        return config('feeds.queue.unique_ttl');
    }
}
