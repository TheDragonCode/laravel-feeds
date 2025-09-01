<?php

declare(strict_types=1);

namespace App\Feeds;

use App\Feeds\Items\UserFeedItem;
use App\Models\User;
use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UserFeed extends Feed
{
    public function builder(): Builder
    {
        return User::query()
            ->whereNotNull('email_verified_at')
            ->where('created_at', '>', now()->subYear());
    }

    public function item(Model $model): FeedItem
    {
        return new UserFeedItem($model);
    }
}
