<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Jobs;

use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Feeds\FeedTarget;
use DragonCode\LaravelFeed\Services\GeneratorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use function app;
use function config;
use function hash;

final class FeedJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $feedClass,
        public ?FeedTarget $target = null,
    ) {
        $this->onConnection(config('feeds.queue.connection'));
        $this->onQueue(config('feeds.queue.name'));
    }

    public function handle(GeneratorService $generator): void
    {
        $generator->feed(
            $this->resolve()
        );
    }

    public function uniqueId(): string
    {
        $identity = $this->target === null
            ? $this->feedClass . "\0default"
            : $this->feedClass . "\0target\0" . $this->target->key;

        return hash('xxh128', $identity);
    }

    public function uniqueFor(): int
    {
        return config('feeds.queue.unique_ttl');
    }

    protected function resolve(): Feed
    {
        $feed = app($this->feedClass);

        return $this->target === null
            ? $feed
            : $feed->forTarget($this->target);
    }
}
