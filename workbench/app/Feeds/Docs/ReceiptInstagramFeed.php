<?php

declare(strict_types=1);

namespace Workbench\App\Feeds\Docs;

use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use DragonCode\LaravelFeed\Presets\InstagramFeedPreset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Workbench\App\Models\Product;

class ReceiptInstagramFeed extends InstagramFeedPreset
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
            ->brand($model->brand) // By default, null
            ->url($model->url)
            ->price(price: $model->price, salePrice: $model->price) // By default, salePrice = price
            ->image($model->images[0])
            ->images($model->images) // By default, null
            ->availability($model->quantity > 0 ? 'in stock' : 'out of stock') // By default, 'in stock'
            ->status($model->quantity > 0 ? 'active' : 'inactive') // By default, 'active'
            ->condition('new')      // By default, 'new'
            ->group(12345)          // By default, null
            ->googleCategory(123)   // By default, null
            ->facebookCategory(456) // By default, null
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
        return '../../../../../../../../../docs/snippets/receipt-instagram-feed.xml';
    }
}
