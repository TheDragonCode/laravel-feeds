<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Presets\Info\YandexFeedInfo;

$info = (new YandexFeedInfo)
    ->category(1, 'Electronics')
    ->rawCategory([
        '@attributes' => [
            'id'       => 2,
            'parentId' => 1,
        ],
        '@value' => 'Smartphones',
    ]);
