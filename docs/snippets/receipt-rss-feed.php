<?php

declare(strict_types=1);

namespace App\Feeds;

use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use DragonCode\LaravelFeed\Presets\RssFeedPreset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Models\News;

class RssFeed extends RssFeedPreset
{
    public function builder(): Builder
    {
        return News::query()->take(2);
    }

    public function item(Model $model): FeedItem
    {
        return parent::item($model)
            ->title($model->title)
            ->description($model->content)
            ->category($model->category)
            ->url($model->url);
    }

    public function filename(): string
    {
        return 'rss.xml';
    }
}
