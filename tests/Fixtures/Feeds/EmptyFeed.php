<?php

declare(strict_types=1);

namespace Tests\Fixtures\Feeds;

use DragonCode\LaravelFeed\Feed;
use Illuminate\Database\Eloquent\Builder;
use Tests\Fixtures\Models\News;

class EmptyFeed extends Feed
{
    public function builder(): Builder
    {
        return News::query()->where('id', '<', 0);
    }

    public function filename(): string
    {
        return 'empty/feed';
    }
}
