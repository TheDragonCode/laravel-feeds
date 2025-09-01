<?php

declare(strict_types=1);

return [
    'channels' => [
        App\Feeds\InstagramFeed::class => (bool) env('FEED_INSTAGRAM_ENABLED', true),
    ],
];
