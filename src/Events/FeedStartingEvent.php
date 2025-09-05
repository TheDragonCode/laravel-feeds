<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Events;

use DragonCode\LaravelFeed\Feeds\Feed;

class FeedStartingEvent
{
    /**
     * Create a new event instance.
     *
     * @param  class-string<Feed>  $feed  Reference to the feed class
     * @return void
     */
    public function __construct(
        public string $feed,
    ) {}
}
