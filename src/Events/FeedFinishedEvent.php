<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Events;

use DragonCode\LaravelFeed\Feeds\Feed;

class FeedFinishedEvent
{
    /**
     * Create a new event instance.
     *
     * @param  class-string<Feed>  $feed  Reference to the feed class
     * @param  string  $path  Path to the generated feed file
     * @return void
     */
    public function __construct(
        public string $feed,
        public string $path,
    ) {}
}
