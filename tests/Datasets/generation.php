<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Models\Feed;

dataset('generation slugs', [
    'latest slug' => fn () => Feed::query()->latest()->first()->slug,
    'oldest slug' => fn () => Feed::query()->oldest()->first()->slug,
]);

dataset('generation invalid', [
    'foo bar',
    '+',
    '-',
    '/',
    '\\',
    '_',
    '',
    'Uppercase',
    'feed_slug',
    '-feed',
    'feed-',
    'feed--title',
    '1.9',
    '+1',
    ' 1 ',
]);
