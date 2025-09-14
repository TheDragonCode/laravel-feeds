<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Feeds\Info;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;

class FeedInfo implements Arrayable
{
    use Conditionable;
    use Macroable;

    public function toArray(): array
    {
        return [];
    }
}
