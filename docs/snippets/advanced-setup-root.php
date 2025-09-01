<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Data\ElementData;
use DragonCode\LaravelFeed\Feeds\Feed;

class UserFeed extends Feed
{
    public function root(): ElementData
    {
        return new ElementData(
            name      : 'users',
            attributes: ['foo' => 'some value']
        );
    }
}
