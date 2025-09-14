<?php

declare(strict_types=1);

namespace App\Feeds\Sitemaps;

use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use DragonCode\LaravelFeed\Presets\SitemapFeedPreset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product;

class ProductFeed extends SitemapFeedPreset
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
        return 'sitemaps/' . parent::filename();
    }
}
