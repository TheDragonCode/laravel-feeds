<?php

declare(strict_types=1);

use Workbench\App\Feeds\EmptyFeed;
use Workbench\App\Feeds\FullFeed;
use Workbench\App\Feeds\PartialFeed;
use Workbench\App\Feeds\SitemapFeed;
use Workbench\App\Feeds\YandexFeed;

dataset('feed classes', [
    'EmptyFeed'   => EmptyFeed::class,
    'FullFeed'    => FullFeed::class,
    'PartialFeed' => PartialFeed::class,
    'SitemapFeed' => SitemapFeed::class,
    'YandexFeed'  => YandexFeed::class,
]);
