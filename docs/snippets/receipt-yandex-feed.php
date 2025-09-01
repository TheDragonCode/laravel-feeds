<?php

declare(strict_types=1);

namespace App\Feeds;

use App\Models\Product;
use DragonCode\LaravelFeed\Data\ElementData;
use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Feeds\Info\FeedInfo;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class YandexFeed extends Feed
{
    public function builder(): Builder
    {
        return Product::query();
    }

    public function root(): ElementData
    {
        return new ElementData('offers');
    }

    public function header(): string
    {
        $date = now()->toIso8601String();

        return <<<XML
            <!DOCTYPE yml_catalog SYSTEM "shops.dtd">
            <yml_catalog date="$date">
                <shop>
            XML;
    }

    public function footer(): string
    {
        return "</shop>\n</yml_catalog>";
    }

    public function info(): FeedInfo
    {
        return new Info\YandexFeedInfo;
    }

    public function filename(): string
    {
        return 'yandex.xml';
    }

    public function item(Model $model): FeedItem
    {
        return new Items\YandexFeedItem($model);
    }
}
