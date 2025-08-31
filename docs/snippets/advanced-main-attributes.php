<?php

declare(strict_types=1);

namespace App\Feeds\Items;

use DragonCode\LaravelFeed\Feeds\Items\FeedItem;

class UserFeedItem extends FeedItem
{
    public function attributes(): array
    {
        return [
            'id'         => $this->model->id,
            'created_at' => $this->model->created_at->format('Y-m-d'),
        ];
    }

    public function toArray(): array
    {
        // ...
    }
}
