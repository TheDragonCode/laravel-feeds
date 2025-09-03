<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Helpers\ScheduleFeedHelper;
use Illuminate\Support\Facades\Schedule;

app(ScheduleFeedHelper::class)->commands();

Schedule::call(function () {
    // ... other action
})->everySecond();
