# Catalog feed patterns

Each feed class is a separate application file. These examples assume an existing `Product` model with string title, description, URL, primary `image_url`, nullable array-cast `images`, integer quantity, float-cast price, and nullable float-cast sale price. The Yandex example also assumes a required `category` relationship and a `Category` model with a string name. Adapt these explicit input contracts to the application before running.

## Instagram primary image and sale price

Run `php artisan make:feed SocialCatalog`, then map the preset fields:

```php
<?php

declare(strict_types=1);

namespace App\Feeds;

use App\Models\Product;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use DragonCode\LaravelFeed\Presets\InstagramFeedPreset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SocialCatalogFeed extends InstagramFeedPreset
{
    public function builder(): Builder
    {
        return Product::query()
            ->whereNotNull('image_url')
            ->where('image_url', '<>', '');
    }

    public function item(Model $model): FeedItem
    {
        return parent::item($model)
            ->title($model->title)
            ->description($model->description)
            ->url($model->url)
            ->image($model->image_url)
            ->images($model->images)
            ->price($model->price, $model->sale_price)
            ->availability($model->quantity > 0 ? 'in stock' : 'out of stock')
            ->status($model->quantity > 0 ? 'active' : 'inactive')
            ->additional(['g:custom_label_0' => 'seasonal']);
    }

    public function filename(): string
    {
        return 'social-catalog.xml';
    }
}
```

This example intentionally excludes products lacking a primary image. Additional images do not substitute for `image()`. A null sale price retains the preset's regular-price fallback. Keep `app.name` and `app.url` correct for the channel header.

## Yandex shop metadata and category references

Run `php artisan make:feed MarketplaceCatalog`. Query category metadata once, eager-load the offer relationship, and use matching category keys:

```php
<?php

declare(strict_types=1);

namespace App\Feeds;

use App\Models\Category;
use App\Models\Product;
use DragonCode\LaravelFeed\Feeds\Info\FeedInfo;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use DragonCode\LaravelFeed\Presets\YandexFeedPreset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MarketplaceCatalogFeed extends YandexFeedPreset
{
    public function builder(): Builder
    {
        return Product::query()->whereHas('category')->with('category');
    }

    public function info(): FeedInfo
    {
        return parent::info()
            ->name('Workshop Store')
            ->company('Workshop Trading')
            ->url('https://shop.example.test')
            ->email('catalog@example.test')
            ->currencies(['RUB' => 1])
            ->categories(Category::query()->pluck('name', 'id')->all());
    }

    public function item(Model $model): FeedItem
    {
        return parent::item($model)
            ->attributeAvailable($model->quantity > 0)
            ->title($model->title)
            ->description($model->description)
            ->url($model->url)
            ->price($model->price)
            ->currencyId('RUB')
            ->images($model->images ?? [])
            ->additional(['categoryId' => $model->category->getKey()]);
    }

    public function filename(): string
    {
        return 'marketplace-catalog.xml';
    }
}
```

The query deliberately excludes uncategorized products. The offer inherits its model ID and `vendor.model` type. The shop info is emitted before `offers`; do not move it inside that container.

For hierarchical or custom categories, build XML directives rather than XML strings. This separate pattern demonstrates mixed normal and raw categories:

```php
<?php

use DragonCode\LaravelFeed\Presets\Info\YandexFeedInfo;

$info = new YandexFeedInfo;
$info->categories([
    20 => 'Tools & supplies',
    21 => [
        '@attributes' => ['id' => 21, 'parentId' => 20],
        '@value' => 'Hand tools',
    ],
]);
$info->rawCategory([
    '@attributes' => ['id' => 22, 'parentId' => 20],
    '@value' => 'Power tools',
]);

assert(count($info->categories) === 3);
assert($info->categories[1]['@attributes']['parentId'] === 20);
assert($info->categories[0]['@value'] === 'Tools & supplies');
```

Raw entries pass through unchanged. Validate IDs, parent links, and array shape before handing them to the preset; the converter handles text escaping.

## Focused checks

Review and apply each generated registration through migrations or deploy operations, then use its actual stored slug with `feed:generate`. Check one sold-out product, one discounted product, escaped catalog text, multiple images, and a category name containing `&`. Assert `g:` elements retain their namespace; verify `g:sale_price`, offer `available`, declared currency IDs, and category references. Compare query counts to item counts to detect a relationship load per row. Run the receiver's catalog validation separately when available.
