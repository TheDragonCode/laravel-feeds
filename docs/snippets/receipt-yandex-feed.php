<?php

declare(strict_types=1);

namespace App\Feeds;

use DragonCode\LaravelFeed\Feeds\Info\FeedInfo;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use DragonCode\LaravelFeed\Presets\YandexFeedPreset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product;

use function config;
use function route;

class YandexFeed extends YandexFeedPreset
{
    public function builder(): Builder
    {
        return Product::query();
    }

    public function info(): FeedInfo
    {
        return parent::info()
            ->email(config('app.email', 'feeds@example.com'))
            ->categories([
                1 => 'Foo',
                2 => 'Bar',
            ]);
    }

    public function item(Model $model): FeedItem
    {
        return parent::item($model)
            ->url(route('products.show', $model->slug))
            ->barcode($model->article)
            ->title($model->title)
            ->description($model->description)
            ->price($model->price)
            ->vendor($model->brand)
            ->images($model->images);
    }

    public function filename(): string
    {
        return 'yandex.xml';
    }
}
