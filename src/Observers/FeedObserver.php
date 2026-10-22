<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Observers;

use DragonCode\LaravelFeed\Models\Feed;

class FeedObserver
{
    public function saving(Feed $feed): void
    {
        $feed->slug = $feed->title;
    }
}
