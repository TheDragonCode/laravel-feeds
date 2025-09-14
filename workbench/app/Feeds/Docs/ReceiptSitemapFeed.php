<?php

declare(strict_types=1);

namespace Workbench\App\Feeds\Docs;

use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use DragonCode\LaravelFeed\Presets\SitemapFeedPreset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Workbench\App\Models\Product;

class ReceiptSitemapFeed extends SitemapFeedPreset
{
    public function builder(): Builder
    {
        return Product::query();
    }

    public function item(Model $model): FeedItem
    {
        return parent::item($model)->url(
            $model->url
        );
    }

    public function filename(): string
    {
        return '../../../../../../../../../docs/snippets/receipt-sitemap-feed.xml';
    }
}
