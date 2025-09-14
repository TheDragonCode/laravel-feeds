<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Feeds\Feed;
use Illuminate\Support\ServiceProvider;

class FeedServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Feed::macro('customFilename', function (Feed $feed, string $name) {
            $this->filename = $name;

            return $this;
        });
    }
}
