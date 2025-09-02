<?php

declare(strict_types=1);

// TODO: Add comments explaining the meanings of the parameters
return [
    'pretty' => (bool) env('FEED_PRETTY', false),

    'table' => [
        'connection' => env('DB_CONNECTION', 'sqlite'),

        'table' => env('FEED_TABLE', 'feeds'),
    ],

    'schedule' => [
        'ttl' => (int) env('FEED_SCHEDULE_TTL', 1440),

        'background' => (bool) env('FEED_SCHEDULE_RUN_BACKGROUND', true),
    ],
];
