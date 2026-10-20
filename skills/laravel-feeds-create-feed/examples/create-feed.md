# Feed Construction Patterns

## Install, Scaffold, and Apply Registration

For a new application installation, first publish the package configuration and table migrations:

```bash
composer require dragon-code/laravel-feeds
php artisan vendor:publish --tag="feeds"
```

Inspect the configured connection/table before migrating. Then scaffold the feed:

```bash
php artisan migrate
php artisan make:feed Product --item
```

Inspect and apply the generated registration artifact after replacing the generated class bodies. Run `php artisan migrate` again when scaffolding created a migration. If it created a deploy operation, use the application's operation runner. Do not run both a generated registration and a second manual creation for the same intended feed.

## Lazy Query and Explicit Item Fields

Application preconditions: `App\Models\Product` has `id`, `title`, `category_id`, `is_exportable`, and date-cast `updated_at` fields, plus a nullable `category` relationship whose model has `id` and `name` fields. Adapt these schema names together rather than copying a query against missing columns.

Put this feed in `app/Feeds/ProductFeed.php`:

```php
<?php

declare(strict_types=1);

namespace App\Feeds;

use App\Feeds\Items\ProductFeedItem;
use App\Models\Product;
use DragonCode\LaravelFeed\Enums\FeedFormatEnum;
use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProductFeed extends Feed
{
    protected FeedFormatEnum $format = FeedFormatEnum::Json;

    protected string $storage = 'local';

    public function builder(): Builder
    {
        return Product::query()
            ->select(['id', 'title', 'category_id', 'updated_at'])
            ->with('category:id,name')
            ->where('is_exportable', true);
    }

    public function item(Model $model): FeedItem
    {
        return new ProductFeedItem($model);
    }
}
```

The `local` disk must be configured and writable. Choose the application's intended disk rather than inheriting public visibility accidentally. The query keeps both the primary key for lazy traversal and the foreign key needed by the eager-loaded relation.

Put the item in `app/Feeds/Items/ProductFeedItem.php`:

```php
<?php

declare(strict_types=1);

namespace App\Feeds\Items;

use DragonCode\LaravelFeed\Attributes\DateFormat;
use DragonCode\LaravelFeed\Data\OptionalData;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;

#[DateFormat('Y-m-d H:i')]
class ProductFeedItem extends FeedItem
{
    public function toArray(): array
    {
        return [
            'id' => $this->model->id,
            'title' => $this->model->title,
            'category' => $this->model->category?->name ?? new OptionalData,
            'updatedAt' => $this->model->updated_at,
        ];
    }
}
```

An absent category is omitted from the JSON object. `updatedAt` remains a raw date object until the item date formatter runs; its timezone still follows `feeds.date.timezone`.

If measured model memory is excessive, override `chunkSize(): int` in this feed with a smaller positive size, such as `500`, then repeat the measurement. A collection returned by `get()` cannot be fixed by lowering that size.

## One-Time Programmatic Registration Alternative

Use this inside a bootstrapped Laravel application only when an existing registration or generated registration artifact does not already own this record:

```php
<?php

use App\Feeds\ProductFeed;
use DragonCode\LaravelFeed\Queries\FeedQuery;

$registration = app(FeedQuery::class)->create(
    class: ProductFeed::class,
    title: 'Partner Catalog',
    expression: '0 */6 * * *',
);

assert($registration->slug === 'partner-catalog');
```

Re-running creation inserts another record; it is not an upsert. Keep titles unique after normalization. Rename through model `update()`/`save()` so the stored slug follows the title.

## Add an Application Registration Attribute

Only create a custom model when registration records need an additional application field. This example uses `is_partner`. Add the field to the configured registration table first, then set the existing `model` key in `config/feeds.php` to `App\Models\Feed::class` while preserving the other settings.

```php
<?php

declare(strict_types=1);

namespace App\Models;

use DragonCode\LaravelFeed\Models\Feed as BaseFeed;

class Feed extends BaseFeed
{
    protected $fillable = [
        'class',
        'title',
        'expression',
        'is_active',
        'last_activity',
        'is_partner',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'is_partner' => 'boolean',
        ];
    }
}
```

In an application migration, add and remove the column on the configured connection/table:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection(config('feeds.table.connection'))
            ->table(config('feeds.table.table'), function (Blueprint $table): void {
                $table->boolean('is_partner')->default(false);
            });
    }

    public function down(): void
    {
        Schema::connection(config('feeds.table.connection'))
            ->table(config('feeds.table.table'), function (Blueprint $table): void {
                $table->dropColumn('is_partner');
            });
    }
};
```

Rollback removes the custom field's stored values. Apply it only when that data removal is intended.

Pass the custom value through controlled `extra` data when creating a distinct registration:

```php
<?php

use App\Feeds\ProductFeed;
use DragonCode\LaravelFeed\Queries\FeedQuery;

$registration = app(FeedQuery::class)->create(
    class: ProductFeed::class,
    title: 'Wholesale Catalog',
    extra: ['is_partner' => true],
);

assert($registration instanceof \App\Models\Feed);
assert($registration->is_partner === true);
assert($registration->slug === 'wholesale-catalog');
```

`extra` can replace standard arguments because it is applied last. Do not populate it from unchecked request input. Multiple registrations of the same feed class still use that class's output path; use targets or separate feed classes when independent files are required.

## Check the Item Contract Before Generation

Run this in the bootstrapped application after adding the classes. It checks field exposure and the absent-relation/date behavior without querying product records:

```php
<?php

use App\Feeds\Items\ProductFeedItem;
use App\Models\Product;
use DragonCode\LaravelFeed\Data\OptionalData;
use Illuminate\Support\Carbon;

$product = new Product;
$product->forceFill([
    'id' => 42,
    'title' => 'Desk Lamp',
    'updated_at' => Carbon::parse('2026-05-04 12:30:00', 'UTC'),
]);
$product->setRelation('category', null);

$fields = new ProductFeedItem($product)->toArray();

assert(array_keys($fields) === ['id', 'title', 'category', 'updatedAt']);
assert($fields['id'] === 42);
assert($fields['category'] instanceof OptionalData);
assert($fields['updatedAt'] instanceof DateTimeInterface);
```

Execute assertions with `zend.assertions=1` and exception assertions enabled, or express the checks as the application's existing test framework assertions. Then generate only the intended registration and verify the parsed file: eligible rows are present, filtered rows are absent, missing categories are omitted, and date strings match the consumer's format/timezone. Include more than one database chunk to catch traversal and relation-loading issues; syntax checks alone do not establish those results.
