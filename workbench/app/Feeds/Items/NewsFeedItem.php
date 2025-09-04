<?php

declare(strict_types=1);

namespace Workbench\App\Feeds\Items;

use DragonCode\LaravelFeed\Feeds\Items\FeedItem;

/** @property-read \Workbench\App\Models\News $model */
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

            'with mixed' => [
                '@mixed' => <<<'XML'
                    <first>line</first>
                    <second>line with <a href="https://example.com">some</a> html/xml tag</second>
                    <third>line with &amp; symbol</third>
                    XML,
            ],
        ];
    }
}
