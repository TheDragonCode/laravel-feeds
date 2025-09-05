<?php

declare(strict_types=1);

use Workbench\App\Data\NewsFakeData;
use Workbench\App\Feeds\PartialFeed;

test('export', function (bool $pretty) {
    setPrettyXml($pretty);

    createNews(static fn () => [
        'updated_at' => fake()->dateTimeBetween(endDate: getDefaultDateTime()->subMonth()->toIso8601String()),
    ]);

    createNews(...NewsFakeData::toArray());

    expectFeedSnapshot(PartialFeed::class);
})->with('boolean');
