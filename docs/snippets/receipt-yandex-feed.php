<?php

declare(strict_types=1);

namespace App\Feeds;

use App\Models\Product;
use DragonCode\LaravelFeed\Feeds\Info\FeedInfo;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use DragonCode\LaravelFeed\Presets\YandexFeedPreset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

use function config;

class YandexFeed extends YandexFeedPreset
{
    public function builder(): Builder
    {
        return Product::query();
    }

    public function info(): FeedInfo
    {
        return parent::info()
            ->name('My App')        // By default, config('app.name')
            ->company('My Company')       // By default, config('app.name')
            ->platform('My Platform')     // By default, config('app.name')
            ->url(config('app.url')) // By default, config('app.url')
            ->email(config('app.email', 'feeds@example.com'))
            ->currencies(['RUR' => 1])   // By default, ['RUR' => 1]
            ->categories([
                1 => 'Foo',
                2 => 'Bar',
            ])
            ->additional([
                'foo' => 'bar',
            ]);
    }

    public function item(Model $model): FeedItem
    {
        return parent::item($model)
            ->attributeId($model->getKey()) // By default, $model->getKey()
            ->attributeAvailable($model->quantity > 0) // By default, true
            ->attributeType('vendor.model') // By default, 'vendor.model'
            ->barcode($model->article)      // By default, null
            ->url($model->url)
            ->title($model->title)
            ->description($model->description)
            ->price($model->price)
            ->currencyId('RUR')     // By default, 'RUR'
            ->vendor($model->brand) // By default, null
            ->images($model->images)
            ->additional([
                'foo' => 'bar',
            ]);
    }

    public function filename(): string
    {
        return 'yandex.xml';
    }
}
