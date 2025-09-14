<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Presets;

use Carbon\Carbon;
use DragonCode\LaravelFeed\Data\ElementData;
use DragonCode\LaravelFeed\Enums\FeedFormatEnum;
use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use DragonCode\LaravelFeed\Presets\Items\RssFeedItem;
use Illuminate\Database\Eloquent\Model;

abstract class RssFeedPreset extends Feed
{
    protected FeedFormatEnum $format = FeedFormatEnum::Rss;

    public function root(): ElementData
    {
        return new ElementData('channel');
    }

    public function item(Model $model): FeedItem
    {
        return (new RssFeedItem($model))
            ->guid($model->getKey())
            ->publishedAt($model->created_at ?? Carbon::now());
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
