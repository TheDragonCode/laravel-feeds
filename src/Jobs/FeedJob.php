<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Jobs;

use DragonCode\LaravelFeed\Contracts\HasFeedTargets;
use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Feeds\FeedTarget;
use DragonCode\LaravelFeed\Services\GeneratorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use LogicException;

use function app;
use function config;
use function hash;
use function sprintf;

final class FeedJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public ?FeedTarget $target = null;

    public function __construct(
        public string $feedClass,
        ?FeedTarget $target = null,
    ) {
        $this->target = $target;

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
        if ($this->target === null) {
            return $this->feedClass;
        }

        return hash('xxh128', $this->feedClass . "\0target\0" . $this->target->key);
    }

    public function uniqueFor(): int
    {
        return config('feeds.queue.unique_ttl');
    }

    protected function resolve(): Feed
    {
        $feed = app($this->feedClass);

        if ($this->target === null) {
            return $feed;
        }

        if (! $feed instanceof HasFeedTargets) {
            throw new LogicException(sprintf('Feed [%s] does not support targets.', $this->feedClass));
        }

        return $feed->forTarget($this->target);
    }
}
