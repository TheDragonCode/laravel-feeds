<?php

declare(strict_types=1);

namespace App\Feeds\Info;

use DragonCode\LaravelFeed\Feeds\Info\FeedInfo;

class UserFeedInfo extends FeedInfo
{
    public function toArray(): array
    {
        return [
            'name'    => config('app.name'),
            'company' => config('app.name'),
        ];
    }
}
