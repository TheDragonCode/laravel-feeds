<?php

declare(strict_types=1);

namespace App\Feeds\Info;

use DragonCode\LaravelFeed\Feeds\Info\FeedInfo;

use function fake;

class AttributesDirectiveFeedInfo extends FeedInfo
{
    public function toArray(): array
    {
        return [
            'company' => [
                '@attributes' => ['since' => fake()->year],
            ],

            'url' => config('app.url'),
        ];
    }
}
