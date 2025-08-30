<?php

declare(strict_types=1);

use Workbench\App\Feeds\EmptyFeed;
use Workbench\App\Feeds\FilledFeed;

return [
    'channels' => [
        // App\Feeds\FooFeed::class => (bool) env('FEED_FOO_ENABLED', true),
        // App\Feeds\BarFeed::class => (bool) env('FEED_BAR_ENABLED', true),
        // App\Feeds\BazFeed::class => (bool) env('FEED_BAZ_ENABLED', false),
        EmptyFeed::class  => true,
        FilledFeed::class => true,
    ],
];
