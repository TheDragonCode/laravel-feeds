<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Feeds\Feed;

class UserFeed extends Feed
{
    public function chunkSize(): int
    {
        return 500;
    }
}
