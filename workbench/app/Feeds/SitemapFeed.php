<?php

declare(strict_types=1);

namespace Workbench\App\Feeds;

use DragonCode\LaravelFeed\Data\ElementData;
use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Workbench\App\Feeds\Items\SitemapFeedItem;
use Workbench\App\Models\Product;

class SitemapFeed extends Feed
{
    protected string $name = 'urlset';

    protected array $attributes = [
        'xmlns'       => 'http://www.sitemaps.org/schemas/sitemap/0.9',
        'xmlns:xhtml' => 'http://www.w3.org/1999/xhtml',
        'xmlns:image' => 'http://www.google.com/schemas/sitemap-image/1.1',
        'xmlns:video' => 'http://www.google.com/schemas/sitemap-video/1.1',
        'xmlns:news'  => 'http://www.google.com/schemas/sitemap-news/0.9',
    ];

    public function builder(): Builder
    {
        return Product::query()->where('quantity', '>', 0);
    }

    public function root(): ElementData
    {
        return new ElementData(
            $this->name,
            $this->attributes
        );
    }

    public function filename(): string
    {
        return 'sitemaps/products.xml';
    }

    public function item(Model $model): FeedItem
    {
        return new SitemapFeedItem($model);
    }
}
