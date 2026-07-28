<?php

declare(strict_types=1);

namespace App\Feeds\Transformers;

use DragonCode\LaravelFeed\Transformers\DateTimeTransformer;

class FeedDateTimeTransformer extends DateTimeTransformer
{
    protected function format(): string
    {
        return 'Y-m-d';
    }
}
