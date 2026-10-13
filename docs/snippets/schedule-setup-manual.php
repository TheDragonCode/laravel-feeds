<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Commands\FeedGenerateCommand;
use Illuminate\Support\Facades\Schedule;

Schedule::command(FeedGenerateCommand::class, ['products'])
    ->withoutOverlapping()
    ->runInBackground()
    ->daily();

Schedule::command(FeedGenerateCommand::class, ['sitemap'])
    ->withoutOverlapping()
    ->runInBackground()
    ->hourly();

Schedule::call(function () {
    // ... other action
})->everySecond();
