<?php

declare(strict_types=1);

use App\Feeds\Info\UserFeedInfo;
use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Feeds\Info\FeedInfo;

class UserFeed extends Feed
{
    public function info(): FeedInfo
    {
        return new UserFeedInfo;
    }
}
