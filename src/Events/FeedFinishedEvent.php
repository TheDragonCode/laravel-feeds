<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Events;

use DragonCode\LaravelFeed\Feeds\Feed;

use function array_values;

final class FeedFinishedEvent
{
    public ?string $target = null;

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
        ?string $target = null,
    ) {
        $this->paths  = array_values($this->paths === [] ? [$this->path] : $this->paths);
        $this->path   = $this->paths[0];
        $this->target = $target;
    }
}
