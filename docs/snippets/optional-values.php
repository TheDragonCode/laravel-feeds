<?php

declare(strict_types=1);

namespace App\Feeds\Items;

use DragonCode\LaravelFeed\Data\OptionalData;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;

class UserFeedItem extends FeedItem
{
    public function toArray(): array
    {
        return [
            'id'   => $this->model->id,
            'name' => $this->model->name,

            'cityId' => $this->model->city?->id ?? new OptionalData,

            'createdAt' => $this->model->created_at,
            'updatedAt' => $this->model->updated_at ?? new OptionalData,
        ];
    }
}
