<?php

declare(strict_types=1);

namespace Workbench\App\Feeds\Docs\Info;

use DragonCode\LaravelFeed\Feeds\Info\FeedInfo;

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
