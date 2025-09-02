<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Models\Feed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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
        enableAllFeeds();

        getAllFeeds()->each(
            fn (Feed $feed) => deleteFeedResult($feed->class)
        );
    });
