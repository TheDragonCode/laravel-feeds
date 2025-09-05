<?php

declare(strict_types=1);

namespace Workbench\App\Feeds\Docs;

use DragonCode\LaravelFeed\Data\ElementData;
use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Workbench\App\Feeds\Docs\Items\ReceiptSitemapFeedItem;
use Workbench\App\Models\Product;

class ReceiptSitemapFeed extends Feed
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
        return Product::query();
    }

    public function root(): ElementData
    {
        return new ElementData(
            name      : $this->name,
            attributes: $this->attributes,
        );
    }

    public function item(Model $model): FeedItem
    {
        return new ReceiptSitemapFeedItem($model);
    }

    public function filename(): string
    {
        return '../../../../../../../../../docs/snippets/receipt-sitemap-feed.xml';
    }
}
