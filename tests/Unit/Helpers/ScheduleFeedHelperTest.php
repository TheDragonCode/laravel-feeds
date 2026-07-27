<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Helpers\ScheduleFeedHelper;
use DragonCode\LaravelFeed\Queries\FeedQuery;

test('skips registration when the feeds table does not exist', function () {
    $query = mock(FeedQuery::class);
    $query->shouldNotReceive('active');

    (new ScheduleFeedHelper($query, false, 60, null, 'missing_feeds'))->commands();
});
