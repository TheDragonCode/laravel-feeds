<?php

declare(strict_types=1);

use Workbench\App\Feeds\EmptyFeed;
use Workbench\App\Feeds\FilledFeed;
use Workbench\App\Feeds\SitemapFeed;
use Workbench\App\Feeds\YandexFeed;

return [
    'channels' => [
        EmptyFeed::class   => true,
        FilledFeed::class  => true,
        SitemapFeed::class => true,
        YandexFeed::class  => true,
    ],
];
