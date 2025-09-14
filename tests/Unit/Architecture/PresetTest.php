<?php

declare(strict_types=1);

arch()
    ->expect('DragonCode\LaravelFeed\Presets')
    ->toHaveSuffix('FeedPreset')
    ->ignoring([
        'DragonCode\LaravelFeed\Presets\Items',
        'DragonCode\LaravelFeed\Presets\Info',
    ]);
