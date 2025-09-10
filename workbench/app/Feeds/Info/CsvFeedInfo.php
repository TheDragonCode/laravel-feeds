<?php

declare(strict_types=1);

namespace Workbench\App\Feeds\Info;

use DragonCode\LaravelFeed\Feeds\Info\FeedInfo;

class CsvFeedInfo extends FeedInfo
{
    public function toArray(): array
    {
        return [
            'id',
            'title',
            'content',
            'created_at',
            'updated_at',
        ];
    }
}
