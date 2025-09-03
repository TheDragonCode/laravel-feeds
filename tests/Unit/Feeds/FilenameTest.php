<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Feeds\Feed;
use Workbench\App\Feeds\EmptyFeed;
use Workbench\App\Feeds\FullFeed;
use Workbench\App\Feeds\PartialFeed;
use Workbench\App\Feeds\SitemapFeed;
use Workbench\App\Feeds\YandexFeed;

test('filename', function (string $class) {
    /** @var Feed $feed */
    $feed = app($class);

    expect($feed->filename())->toMatchSnapshot();
})->with([
    EmptyFeed::class,
    FullFeed::class,
    PartialFeed::class,
    SitemapFeed::class,
    YandexFeed::class,
]);
