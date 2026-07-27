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

use function app;
use function config;

final class FeedJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $feedClass
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
        return $this->feedClass;
    }

    public function uniqueFor(): int
    {
        return config('feeds.queue.unique_ttl');
    }

    protected function resolve(): Feed
    {
        return app($this->feedClass);
    }
}
