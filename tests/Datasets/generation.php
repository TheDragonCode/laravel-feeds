<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Models\Feed;

dataset('generation ids', [
    'latest id' => fn () => Feed::query()->latest()->first()->id,
    'oldest id' => fn () => Feed::query()->oldest()->first()->id,
]);

dataset('generation invalid', [
    'foo bar',
    '+',
    '-',
    '/',
    '\\',
    '_',
]);
