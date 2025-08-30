<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Feeds\Sitemaps;

use App\Models\Post;
use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PostFeed extends Feed
{
    public function builder(): Builder
    {
        return Post::query()
            ->withCount('comments')
            ->with('author')
            ->where('created_at', '>', now()->subYear());
    }

    public function rootItem(): ?string
    {
        return 'urlset';
    }

    public function filename(): string
    {
        return 'sitemaps/posts.xml';
    }

    public function item(Model $model): FeedItem
    {
        return new Items\Sitemaps\PostFeedItem($model);
    }
}
