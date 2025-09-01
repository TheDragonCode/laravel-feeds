<?php

declare(strict_types=1);

namespace App\Feeds;

use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Feeds\Info\FeedInfo;

class UserFeed extends Feed
{
    public function info(): FeedInfo
    {
        return new FeedInfo;
    }
}
