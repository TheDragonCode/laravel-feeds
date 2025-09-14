<?php

declare(strict_types=1);

namespace Workbench\App\Feeds\Info;

use DragonCode\LaravelFeed\Feeds\Info\FeedInfo;

use function config;

class RssFeedInfo extends FeedInfo
{
    public function toArray(): array
    {
        return [
            'title' => config('app.name'),
            'link'  => config('app.url'),

            'description' => 'RSS Feed for ' . config('app.name'),

            'language' => config('app.locale'),

            'ttl' => 10,

            'atom:link' => [
                '@attributes' => [
                    'rel'  => 'self',
                    'type' => 'application/rss+xml',
                    'href' => config('app.url') . '/rss.xml',
                ],
            ],
        ];
    }
}
