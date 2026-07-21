<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Feeds\Feed;

class UserFeed extends Feed
{
    protected string $storage = 's3';

    public function filename(): string
    {
        return 'some/path/will/be/here.xml';
    }
}
