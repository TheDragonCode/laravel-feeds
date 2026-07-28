<?php

declare(strict_types=1);

arch()
    ->expect('DragonCode\LaravelFeed\Casts')
    ->toHaveSuffix('Cast');

arch()
    ->expect('DragonCode\LaravelFeed\Casts')
    ->toBeFinal();
