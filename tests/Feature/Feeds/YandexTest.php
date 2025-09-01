<?php

declare(strict_types=1);

use Workbench\App\Feeds\YandexFeed;

test('export', function () {
    createProducts();

    expectFeed(YandexFeed::class);
});
