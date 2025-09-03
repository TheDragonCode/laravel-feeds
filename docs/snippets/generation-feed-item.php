<?php

declare(strict_types=1);

namespace App\Feeds\Items;

use DragonCode\LaravelFeed\Feeds\Items\FeedItem;

/** @property-read \App\Models\User $model */
class UserFeedItem extends FeedItem
{
    public function toArray(): array
    {
        return [
            'name'  => $this->model->class,
            'email' => $this->model->email,
        ];
    }
}
