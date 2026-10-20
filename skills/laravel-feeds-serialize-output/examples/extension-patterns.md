# Extension patterns

## Feed-specific transformers

Use this decimal formatting only when every float in the selected feed should
become a two-decimal string. For a single price field, format it in that item's
mapping instead of applying a broad transformer.

```php
namespace App\Feeds\Transformers;

use DragonCode\LaravelFeed\Contracts\Transformer;

class DecimalTransformer implements Transformer
{
    public function allow(mixed $value): bool
    {
        return is_float($value);
    }

    public function transform(mixed $value): string
    {
        return number_format($value, 2, '.', '');
    }
}
```

```php
namespace App\Feeds\Transformers;

use DragonCode\LaravelFeed\Transformers\DateTimeTransformer;

class CalendarDateTransformer extends DateTimeTransformer
{
    protected function format(): string
    {
        return 'Y-m-d';
    }
}
```

Override `transformers()` on the existing feed; these entries prepend rather
than replace global boolean/date/enum transformers.

```php
namespace App\Feeds;

use App\Feeds\Transformers\CalendarDateTransformer;
use App\Feeds\Transformers\DecimalTransformer;

class CalendarCatalogFeed extends ProductExportFeed
{
    public function transformers(): array
    {
        return [CalendarDateTransformer::class, DecimalTransformer::class];
    }
}
```

To register a transformer globally, preserve the existing `transformers` list
in `config/feeds.php` and append its class. Only do this when all feeds should
change. Constructor dependencies can use normal container injection.

Run in a bootstrapped application with the classes above:

```php
use App\Feeds\Transformers\DecimalTransformer;
use DragonCode\LaravelFeed\Services\TransformerService;

$pipeline = app(TransformerService::class)->pipeline(
    prependTransformers: [DecimalTransformer::class]
);

assert($pipeline(12.5) === '12.50');
assert($pipeline('12.5') === '12.5');
assert($pipeline(true) === 'true');
```

Return raw date objects when selecting `CalendarDateTransformer`; a date string
is already outside its `allow()` condition. Item `DateFormat` takes precedence
over this transformer, but later string transformers can still change its result.

## Conditional item construction

When an item has a reusable construction option, keep its mutation explicit.
This item can replace the base item in the shared product feed.

```php
namespace App\Feeds\Items;

use DragonCode\LaravelFeed\Feeds\Items\FeedItem;

class LabeledProductItem extends FeedItem
{
    public string $suffix = '';

    public function toArray(): array
    {
        return ['title' => $this->model->title . $this->suffix];
    }
}
```

For a Product model with a boolean `featured` column, select it in a feed subclass
and keep the namespace imports outside the method:

```php
namespace App\Feeds;

use App\Feeds\Items\LabeledProductItem;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class LabeledCatalogFeed extends ProductExportFeed
{
    public function builder(): Builder
    {
        return parent::builder()->addSelect('featured');
    }

    public function item(Model $model): FeedItem
    {
        return new LabeledProductItem($model)->when(
            (bool) $model->featured,
            function (LabeledProductItem $item): void {
                $item->suffix = ' [featured]';
            },
            function (LabeledProductItem $item): void {
                $item->suffix = ' [standard]';
            }
        );
    }
}
```

`unless()` reverses which branch runs. Check both branches when the exported
label matters. A direct expression in `toArray()` is smaller for a one-off label.

## Shared macro only for existing cross-feed reuse

If several existing item classes need the same normalization, register it in an
application service provider. A macro closure is bound to the item instance; it
does not need the instance as an extra argument.

```php
namespace App\Providers;

use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use Illuminate\Support\ServiceProvider;

class FeedFormattingProvider extends ServiceProvider
{
    public function boot(): void
    {
        FeedItem::macro('trimmedTitle', function (): string {
            return trim((string) $this->model->title);
        });
    }
}
```

Register the provider through the application's existing provider mechanism.
An item can then call `$this->trimmedTitle()` from `toArray()`. `Feed` and
`FeedInfo` support the same macro mechanism. Avoid replacing an existing macro
name, and isolate macro registration in tests so global state does not leak.
