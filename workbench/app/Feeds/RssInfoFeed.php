<?php

declare(strict_types=1);

namespace Workbench\App\Feeds;

use DragonCode\LaravelFeed\Data\ElementData;
use DragonCode\LaravelFeed\Enums\FeedFormatEnum;
use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Feeds\Info\FeedInfo;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Workbench\App\Feeds\Info\RssFeedInfo;
use Workbench\App\Feeds\Items\RssFeedItem;
use Workbench\App\Models\News;

class RssInfoFeed extends Feed
{
    protected FeedFormatEnum $format = FeedFormatEnum::Rss;

    public function builder(): Builder
    {
        return News::query();
    }

    public function root(): ElementData
    {
        return new ElementData('channel');
    }

    public function info(): FeedInfo
    {
        return new RssFeedInfo;
    }

    public function item(Model $model): FeedItem
    {
        return new RssFeedItem($model);
    }

    public function header(): string
    {
        return implode("\n", [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:atom="http://www.w3.org/2005/Atom">',
        ]);
    }

    public function footer(): string
    {
        return '</rss>';
    }
}
