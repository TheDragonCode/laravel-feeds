<?php

declare(strict_types=1);

namespace Workbench\App\Feeds;

use DragonCode\LaravelFeed\Data\ElementData;
use DragonCode\LaravelFeed\Feeds\Feed;
use Illuminate\Database\Eloquent\Builder;
use Workbench\App\Models\News;

class EmptyFeed extends Feed
{
    public function builder(): Builder
    {
        return News::query()->where('id', '<', 0);
    }

    public function root(): ElementData
    {
        return new ElementData;
    }
}
