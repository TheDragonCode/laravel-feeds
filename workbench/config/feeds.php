<?php

declare(strict_types=1);

use Workbench\App\Feeds\EmptyFeed;
use Workbench\App\Feeds\FullFeed;
use Workbench\App\Feeds\PartialFeed;
use Workbench\App\Feeds\SitemapFeed;
use Workbench\App\Feeds\YandexFeed;

return [
    'channels' => [
        EmptyFeed::class   => true,
        FullFeed::class    => true,
        PartialFeed::class => true,

        SitemapFeed::class => true,
        YandexFeed::class  => true,
    ],
];
