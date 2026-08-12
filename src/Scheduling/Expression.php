<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Scheduling;

use Illuminate\Console\Scheduling\ManagesFrequencies;
use Stringable;

final class Expression implements Stringable
{
    use ManagesFrequencies {
        between as private;
        unlessBetween as private;
        everySecond as private;
        everyTwoSeconds as private;
        everyFiveSeconds as private;
        everyTenSeconds as private;
        everyFifteenSeconds as private;
        everyTwentySeconds as private;
        everyThirtySeconds as private;
        lastDayOfMonth as private;
        timezone as private;
    }

    public string $expression = '* * * * *';

    public function __toString(): string
    {
        return $this->expression;
    }
}
