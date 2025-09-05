<?php

declare(strict_types=1);

namespace Workbench\App\Feeds\Docs\Info;

use DragonCode\LaravelFeed\Feeds\Info\FeedInfo;

class ReceiptYandexFeedInfo extends FeedInfo
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
                ],
            ],
        ];
    }
}
