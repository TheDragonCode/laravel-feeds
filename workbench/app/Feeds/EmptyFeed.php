<?php

declare(strict_types=1);

namespace Workbench\App\Feeds;

use DragonCode\LaravelFeed\Feeds\Feed;
use Illuminate\Database\Eloquent\Builder;
use Workbench\App\Models\News;

class EmptyFeed extends Feed
{
    public function builder(): Builder
    {
        return News::query()->where('id', '<', 0);
    }

    public function header(): string
    {
        return '';
    }
}
