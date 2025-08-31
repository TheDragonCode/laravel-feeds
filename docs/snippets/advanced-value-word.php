<?php

declare(strict_types=1);

namespace App\Feeds\Items;

use DragonCode\LaravelFeed\Feeds\Items\FeedItem;

class UserFeedItem extends FeedItem
{
    public function toArray(): array
    {
        return [
            '@param' => [
                [
                    '@attributes' => ['name' => 'Article'],
                    '@value'      => $this->model->article,
                ],
                [
                    '@attributes' => ['name' => 'Brand'],
                    '@value'      => $this->model->brand,
                ],
            ],
        ];
    }
}
