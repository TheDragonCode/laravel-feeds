<?php

declare(strict_types=1);

return [
    'channels' => [
        App\Feeds\Sitemaps\ProductFeed::class => (bool) env('FEED_SITEMAP_POSTS_ENABLED', true),
    ],
];
