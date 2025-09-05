<?php

declare(strict_types=1);

namespace Workbench\App\Feeds\Items;

use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use RuntimeException;

class FailedFeedItem extends FeedItem
{
    public function toArray(): array
    {
        throw new RuntimeException(
            'Something went wrong while generating the feed.'
        );
    }
}
