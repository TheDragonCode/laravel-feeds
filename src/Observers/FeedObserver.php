<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Observers;

use DragonCode\LaravelFeed\Models\Feed;

class FeedObserver
{
    public function saving(Feed $feed): void
    {
        if (! $feed->wasChanged('slug')) {
            $feed->slug = $feed->title;
        }
    }
}
