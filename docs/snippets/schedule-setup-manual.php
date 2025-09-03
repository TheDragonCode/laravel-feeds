<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Console\Commands\FeedGenerateCommand;
use Illuminate\Support\Facades\Schedule;

Schedule::command(FeedGenerateCommand::class, [111])
    ->withoutOverlapping()
    ->runInBackground()
    ->daily();

Schedule::command(FeedGenerateCommand::class, [222])
    ->withoutOverlapping()
    ->runInBackground()
    ->hourly();

Schedule::call(function () {
    // ... other action
})->everySecond();
