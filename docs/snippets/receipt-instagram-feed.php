<?php

declare(strict_types=1);

namespace App\Feeds;

use App\Models\Product;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use DragonCode\LaravelFeed\Presets\InstagramFeedPreset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class InstagramFeed extends InstagramFeedPreset
{
    public function builder(): Builder
    {
        return Product::query();
    }

    public function item(Model $model): FeedItem
    {
        return parent::item($model)
            ->title($model->title)
            ->description($model->description)
            ->brand($model->brand)
            ->url(route('products.show', $model->slug))
            ->price($model->price)
            ->image(Arr::first($model->images))
            ->images($model->images)
            ->availability($model->quantity > 0 ? 'in stock' : 'out of stock')
            ->status($model->quantity > 0 ? 'active' : 'inactive')
            ->additional([
                'g:foo' => 'Some foo',
                'g:bar' => 'Some bar',

                'g:baz' => [
                    '@attributes' => ['qwe' => 'rty'],
                    '@value'      => 'Some baz',
                ],

                '@g:arrayable' => [
                    'a',
                    'b',
                    'c',
                ],
            ]);
    }

    public function filename(): string
    {
        return 'instagram.xml';
    }
}
