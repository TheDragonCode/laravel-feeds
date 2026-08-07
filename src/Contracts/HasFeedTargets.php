<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Contracts;

use DragonCode\LaravelFeed\Feeds\FeedTarget;

interface HasFeedTargets
{
    public function forTarget(FeedTarget $target): static;

    public function target(): FeedTarget;

    /** @return iterable<FeedTarget> */
    public function targets(): iterable;

    public function findTarget(string $key): ?FeedTarget;
}
