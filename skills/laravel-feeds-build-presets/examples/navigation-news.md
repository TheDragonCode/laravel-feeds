# Navigation and news feeds

Each PHP block is a separate application file. These examples assume existing Eloquent models: `Product` exposes a URL and datetime-cast `updated_at`; `Article` exposes title, URL, body, datetime-cast `created_at`, and an optional `category` relationship whose model has a string `name`.

## Sitemap with stable destination

Create the definition with `php artisan make:feed ProductSitemap`. Replace its base class and mapping:

```php
<?php

declare(strict_types=1);

namespace App\Feeds;

use App\Models\Product;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use DragonCode\LaravelFeed\Presets\SitemapFeedPreset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProductSitemapFeed extends SitemapFeedPreset
{
    public function builder(): Builder
    {
        return Product::query()->whereNotNull('updated_at');
    }

    public function item(Model $model): FeedItem
    {
        return parent::item($model)
            ->url($model->url)
            ->priority(0.8);
    }

    public function filename(): string
    {
        return 'sitemaps/products.xml';
    }
}
```

The query deliberately excludes products without a modification timestamp, because the preset initializes its date before the subclass mapping. Confirm this filtering rule with the application contract.

After reviewing and applying the generated registration, obtain its stored slug and generate it. For a registration stored as `Product Sitemap`:

```bash
php artisan feed:generate product-sitemap
```

Keep the inherited 50,000-row split limit. With the default public disk and its standard public storage link, a generated file can be addressed as `https://shop.example.test/storage/sitemaps/products.xml`. Verify the actual disk/link configuration and suffixed output names before adding a root sitemap index entry.

## RSS channel metadata and optional category relationship

Create the definition with `php artisan make:feed ArticleNews`. This adds channel metadata without changing the preset's RSS wrapper:

```php
<?php

declare(strict_types=1);

namespace App\Feeds;

use App\Models\Article;
use DragonCode\LaravelFeed\Feeds\Info\FeedInfo;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use DragonCode\LaravelFeed\Presets\RssFeedPreset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ArticleNewsFeed extends RssFeedPreset
{
    public function builder(): Builder
    {
        return Article::query()->with('category');
    }

    public function info(): FeedInfo
    {
        return new class extends FeedInfo {
            public function toArray(): array
            {
                return [
                    'title' => 'Store updates',
                    'link' => 'https://shop.example.test/news',
                    'description' => 'Product and service announcements',
                ];
            }
        };
    }

    public function item(Model $model): FeedItem
    {
        $item = parent::item($model)
            ->title($model->title)
            ->url($model->url)
            ->description($model->body);

        if ($model->category !== null) {
            $item->category($model->category->name);
        }

        return $item;
    }

    public function filename(): string
    {
        return 'news.rss';
    }
}
```

The parent keeps GUIDs tied to model keys and publication dates tied to `created_at`, with a current-time fallback. The eager load avoids one category query per item. A missing category omits that field.

## Focused checks

Generate fixtures containing an optional missing category, text with `&` and `<`, and a known date. Parse output as XML; check `urlset/url/loc` for Sitemap and `rss/channel/item` for RSS. Confirm channel metadata, unchanged GUIDs and dates on repeated runs, text round-trip, and the expected number of records. RSS output must remain RSS 2.0 even though its wrapper declares an Atom namespace.
