<?php

declare(strict_types=1);

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;

arch()
    ->expect('DragonCode\LaravelFeed\Jobs')
    ->toBeFinal();

arch()
    ->expect('DragonCode\LaravelFeed\Jobs')
    ->toHaveSuffix('Job');

arch()
    ->expect('DragonCode\LaravelFeed\Jobs')
    ->toImplement([ShouldQueue::class, ShouldBeUnique::class]);

arch()
    ->expect('DragonCode\LaravelFeed\Jobs')
    ->toHaveMethod('uniqueId');

arch()
    ->expect('DragonCode\LaravelFeed\Jobs')
    ->toHaveMethod('uniqueFor');
