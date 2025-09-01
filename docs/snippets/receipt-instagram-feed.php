<?php

declare(strict_types=1);

namespace App\Feeds;

use App\Models\Product;
use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

use function config;

class InstagramFeed extends Feed
{
    public function builder(): Builder
    {
        return Product::query();
    }

    public function header(): string
    {
        $name = config('app.name');
        $url  = config('app.url');

        return <<<XML
            <rss xmlns:g="http://base.google.com/ns/1.0" version="2.0">
            <channel>
                <title>$name</title>
                <link>$url</link>

            XML;
    }

    public function footer(): string
    {
        return "\n</channel>\n</rss>";
    }

    public function filename(): string
    {
        return 'instagram.xml';
    }

    public function item(Model $model): FeedItem
    {
        return new Items\InstagramFeedItem($model);
    }
}
