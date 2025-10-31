<?php

declare(strict_types=1);

namespace App\Feeds;

use App\Models\User;
use DragonCode\LaravelFeed\Feeds\Feed;
use Illuminate\Database\Eloquent\Builder;

class AttributeFeed extends Feed
{
    public function builder(): Builder
    {
        return User::query();
    }

    public function perFile(): int
    {
        return 100;
    }

    public function maxFiles(): int
    {
        return 10;
    }
}
