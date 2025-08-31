<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Feeds\Feed;

class UserFeed extends Feed
{
    protected string $storage = 'public';
}
