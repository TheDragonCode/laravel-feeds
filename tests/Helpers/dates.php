<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;

function getDefaultDateTime(): Carbon
{
    return Carbon::now();
}

function setDefaultDateTime(): void
{
    Carbon::setTestNow('2025-09-04 04:08:12');
}
