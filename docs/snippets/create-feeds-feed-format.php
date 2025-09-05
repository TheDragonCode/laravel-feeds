<?php

declare(strict_types=1);

namespace App\Feeds;

use App\Models\User;
use DragonCode\LaravelFeed\Enums\FeedFormatEnum;
use DragonCode\LaravelFeed\Feeds\Feed;
use Illuminate\Database\Eloquent\Builder;

class UserFeed extends Feed
{
    protected FeedFormatEnum $format = FeedFormatEnum::Json;

    public function builder(): Builder
    {
        //
    }
}
