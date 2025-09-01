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
            'name'  => $this->model->name,
            'email' => $this->model->email,

            'header' => [
                '@attributes' => [
                    'my-key-1' => 'my value 1',
                    'my-key-2' => 'my value 2',
                ],
                '@cdata' => '<h1>' . $this->model->name . '</h1>',
            ],
        ];
    }
}
