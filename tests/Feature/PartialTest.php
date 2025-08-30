<?php

declare(strict_types=1);

use Workbench\App\Data\NewsFakeData;
use Workbench\App\Feeds\PartialFeed;

test('export', function (bool $pretty) {
    setPrettyXml($pretty);

    createNews(static fn () => [
        'updated_at' => fake()->dateTimeBetween(endDate: '-1 month'),
    ]);

    createNews(...NewsFakeData::toArray());

    expectFeed(PartialFeed::class);
})->with('boolean');
