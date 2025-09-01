<?php

declare(strict_types=1);

return [
    'channels' => [
        App\Feeds\YandexFeed::class => (bool) env('FEED_YANDEX_ENABLED', true),
    ],
];
