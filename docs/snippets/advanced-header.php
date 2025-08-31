<?php

declare(strict_types=1);

namespace App\Feeds;

use DragonCode\LaravelFeed\Feeds\Feed;

class UserFeed extends Feed
{
    public function header(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>';
    }
}
