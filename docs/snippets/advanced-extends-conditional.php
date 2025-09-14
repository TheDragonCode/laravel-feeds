<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use Illuminate\Database\Eloquent\Model;

class ProductFeed extends Feed
{
    public function item(Model $model): FeedItem
    {
        return (new ProductFeedItem($model))
            ->when($model->category, function (ProductFeedItem $item) {
                $item->title .= ' (first)';
            });
    }
}
