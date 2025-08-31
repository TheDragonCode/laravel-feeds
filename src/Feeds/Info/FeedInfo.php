<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Feeds\Info;

use Illuminate\Contracts\Support\Arrayable;

class FeedInfo implements Arrayable
{
    public function toArray(): array
    {
        return [];
    }
}
