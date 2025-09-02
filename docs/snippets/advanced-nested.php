<?php

declare(strict_types=1);

namespace App\Feeds\Items;

use DragonCode\LaravelFeed\Feeds\Items\FeedItem;

class UserFeedItem extends FeedItem
{
    public function toArray(): array
    {
        return [
            'name'  => $this->model->class,
            'email' => $this->model->email,

            'header' => [
                '@cdata' => '<h1>' . $this->model->class . '</h1>',
            ],

            'names' => [
                'Good guy' => [
                    '@attributes' => [
                        'my-key-1' => 'my value 1',
                        'my-key-2' => 'my value 2',
                    ],

                    'name'   => 'Luke Skywalker',
                    'weapon' => 'Lightsaber',
                ],

                'Bad guy' => [
                    'name' => [
                        '@cdata' => '<h1>Sauron</h1>',
                    ],

                    'weapon' => 'Evil Eye',
                ],
            ],
        ];
    }
}
