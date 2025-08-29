<?php

declare(strict_types=1);

namespace Tests\Fixtures\Feeds;

use DragonCode\LaravelFeed\Data\FeedItem;
use DragonCode\LaravelFeed\Feed;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Tests\Fixtures\Feeds\Items\NewsFeedItem;
use Tests\Fixtures\Models\News;

use function now;

class FilledFeed extends Feed
{
    public function builder(): Builder
    {
        return News::query()->where('updated_at', '>', now()->subDay());
    }

    public function header(): string
    {
        return parent::header() . "\n<news>\n";
    }

    public function footer(): string
    {
        return '</news>';
    }

    public function filename(): string
    {
        return 'partial/feed';
    }

    public function item(Model $model): FeedItem
    {
        return new NewsFeedItem($model);
    }
}
