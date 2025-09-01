<?php

declare(strict_types=1);

use Workbench\App\Feeds\SitemapFeed;

test('export', function () {
    createProducts();

    expectFeed(SitemapFeed::class);
});
