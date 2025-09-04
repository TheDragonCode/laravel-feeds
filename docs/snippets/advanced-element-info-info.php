<?php

declare(strict_types=1);

namespace App\Feeds\Info;

use DragonCode\LaravelFeed\Feeds\Info\FeedInfo;

use function config;

class InfoMethodFeedInfo extends FeedInfo
{
    public function toArray(): array
    {
        return [
            'company' => config('app.name'),
            'url'     => config('app.url'),
        ];
    }
}
