<?php

declare(strict_types=1);

namespace App\Feeds\Items;

use DragonCode\LaravelFeed\Feeds\Items\FeedItem;

class UserFeedItem extends FeedItem
{
    public function toArray(): array
    {
        return [
            '@picture' => $this->model->images,
        ];
    }
}
