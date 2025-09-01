<?php

declare(strict_types=1);

namespace App\Feeds\Info;

use DragonCode\LaravelFeed\Feeds\Info\FeedInfo;

class YandexFeedInfo extends FeedInfo
{
    public function toArray(): array
    {
        return [
            'name'     => config('app.name'),
            'company'  => config('app.name'),
            'platform' => config('app.name'),

            'url'   => config('app.url'),
            'email' => config('emails.manager'),

            'currencies' => [
                '@currency' => [
                    [
                        '@attributes' => [
                            'id'   => 'RUR',
                            'rate' => '1',
                        ],
                    ],
                ],
            ],

            'categories' => [
                '@category' => [
                    [
                        '@attributes' => ['id' => 41],
                        '@value'      => 'Домашние майки',
                    ],
                    [
                        '@attributes' => ['id' => 539],
                        '@value'      => 'Велосипедки',
                    ],
                    [
                        '@attributes' => ['id' => 44],
                        '@value'      => 'Ремни',
                    ],
                ],
            ],
        ];
    }
}
