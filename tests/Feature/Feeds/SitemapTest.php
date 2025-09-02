<?php

declare(strict_types=1);

use Workbench\App\Feeds\SitemapFeed;

test('export', function () {
    createProducts();

    expectFeedSnapshot(SitemapFeed::class);
});
