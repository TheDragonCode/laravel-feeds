<?php

declare(strict_types=1);

namespace App\Feeds\Items;

use DragonCode\LaravelFeed\Feeds\Items\FeedItem;

class UserFeedItem extends FeedItem
{
    protected ?string $name = 'users';

    public function toArray(): array
    {
        return [
            'some' => [
                '@attributes' => [
                    'foo' => 'bar',
                    'qwe' => 'rty',
                ],
            ],
        ];
    }
}
