<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Models\Feed;
use Workbench\App\Feeds\YandexFeed;

test('export', function () {
    createProducts();

    dd(
        Feed::query()->pluck('class')->all(),
        '---------------',
        YandexFeed::class,
    );

    expectFeed(YandexFeed::class);
});
