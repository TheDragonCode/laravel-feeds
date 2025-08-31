<?php

declare(strict_types=1);

namespace Workbench\App\Feeds;

use DragonCode\LaravelFeed\Data\ElementData;
use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Feeds\Info\FeedInfo;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Workbench\App\Feeds\Info\YandexFeedInfo;
use Workbench\App\Models\Product;

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
        $date = '2025-08-30T21:14:49+00:00';

        return <<<XML
            <!DOCTYPE yml_catalog SYSTEM "shops.dtd">
            <yml_catalog date="$date">
                <shop>
            XML;
    }

    public function footer(): string
    {
        return "\n</shop>\n</yml_catalog>";
    }

    public function info(): FeedInfo
    {
        return new YandexFeedInfo;
    }

    public function item(Model $model): FeedItem
    {
        return new Items\YandexFeedItem($model);
    }

    public function filename(): string
    {
        return 'yandex.xml';
    }
}
