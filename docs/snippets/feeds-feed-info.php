<?php

declare(strict_types=1);

namespace App\Feeds\Items;

use DragonCode\LaravelFeed\Feeds\Info\FeedInfo;

class UserFeedInfo extends FeedInfo
{
    public function toArray(): array
    {
        return [
            'company'  => config('app.name'),
            'platform' => config('app.name'),

            'url'   => config('app.url'),
            'email' => config('emails.manager'),
        ];
    }
}
