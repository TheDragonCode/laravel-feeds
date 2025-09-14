<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Presets;

use DragonCode\LaravelFeed\Data\ElementData;
use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Feeds\Info\FeedInfo;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use DragonCode\LaravelFeed\Presets\Info\YandexFeedInfo;
use DragonCode\LaravelFeed\Presets\Items\YandexFeedItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

abstract class YandexFeedPreset extends Feed
{
    public function root(): ElementData
    {
        return new ElementData('offers', beforeInfo: false);
    }

    public function header(): string
    {
        $date = Carbon::now()->toIso8601String();

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
        return new YandexFeedInfo;
    }

    public function item(Model $model): FeedItem
    {
        return new YandexFeedItem($model);
    }
}
