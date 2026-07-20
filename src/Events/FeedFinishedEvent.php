<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Events;

use DragonCode\LaravelFeed\Feeds\Feed;

use function array_values;

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
        public array $paths = [],
    ) {
        $this->paths = array_values($this->paths === [] ? [$this->path] : $this->paths);
        $this->path  = $this->paths[0];
    }
}
