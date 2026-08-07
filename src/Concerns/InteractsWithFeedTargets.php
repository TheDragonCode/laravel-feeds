<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Concerns;

use DragonCode\LaravelFeed\Feeds\FeedTarget;
use LogicException;

use function sprintf;

trait InteractsWithFeedTargets
{
    private ?FeedTarget $feedTarget = null;

    public function forTarget(FeedTarget $target): static
    {
        $feed = clone $this;

        $feed->feedTarget = $target;
        $feed->filename   = null;

        return $feed;
    }

    public function target(): FeedTarget
    {
        return $this->feedTarget
            ?? throw new LogicException(sprintf('Feed [%s] has no target.', static::class));
    }
}
