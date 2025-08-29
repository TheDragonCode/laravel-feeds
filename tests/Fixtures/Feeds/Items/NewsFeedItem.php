<?php

declare(strict_types=1);

namespace Tests\Fixtures\Feeds\Items;

use DragonCode\LaravelFeed\Data\FeedItem;

/** @property-read \Tests\Fixtures\Models\News $model */
class NewsFeedItem extends FeedItem
{
    public function toArray(): array
    {
        return [
            'record title'   => '[NEWS]:' . $this->model->title,
            'record content' => $this->model->content,

            'extra' => 'Some extra data',

            'with attributes' => [
                'Good guy' => [
                    '_attributes' => [
                        'my key 1' => 'my value 1',
                        'my key 2' => 'my value 2',
                    ],

                    'name'   => 'Luke Skywalker',
                    'weapon' => 'Lightsaber',
                ],

                'Bad guy' => [
                    'name' => [
                        '_cdata' => '<h1>Sauron</h1>',
                    ],

                    'weapon' => 'Evil Eye',
                ],
            ],
        ];
    }
}
