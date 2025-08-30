<?php

declare(strict_types=1);

namespace Workbench\App\Feeds;

use DragonCode\LaravelFeed\Feed;
use Illuminate\Database\Eloquent\Builder;
use Workbench\App\Models\News;

use function class_basename;

class EmptyFeed extends Feed
{
    public function builder(): Builder
    {
        return News::query()->where('id', '<', 0);
    }

    public function rootItem(): ?string
    {
        return class_basename($this);
    }

    public function filename(): string
    {
        return 'empty/feed.xml';
    }
}
