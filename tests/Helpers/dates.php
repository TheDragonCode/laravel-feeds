<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;

function getDefaultDateTime(): Carbon
{
    return Carbon::now();
}

function setDefaultDateTime(): void
{
    config()->set('app.timezone', '+01:00');

    Carbon::setTestNow('2025-09-04 04:08:12');
}
