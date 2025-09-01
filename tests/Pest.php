<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Workbench\App\Feeds\FullFeed;
use Workbench\App\Feeds\PartialFeed;
use Workbench\App\Feeds\SitemapFeed;
use Workbench\App\Feeds\YandexFeed;

pest()
    ->printer()
    ->compact();

pest()
    ->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()
    ->extend(TestCase::class)
    ->in('Unit');

pest()
    ->in('Feature/Console/Generation')
    ->beforeEach(function () {
        config()?->set('feeds.channels', [
            FullFeed::class    => true,
            PartialFeed::class => true,
            SitemapFeed::class => true,
            YandexFeed::class  => true,
        ]);

        config()
            ?->collection('feeds.channels')
            ?->keys()
            ?->each(fn (string $feed) => deleteFeedResult($feed));
    });
