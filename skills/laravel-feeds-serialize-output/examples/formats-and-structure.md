# Formats and structure

## Shared explicit item mapping

Assume an application `Product` model with `id`, `title`, and `price` columns.
Place each named class in its matching autoloaded file. The base class remains
usable as XML; its subclasses reuse only the query and mapping.

```php
namespace App\Feeds;

use App\Models\Product;
use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProductExportFeed extends Feed
{
    public function builder(): Builder
    {
        return Product::query()->select(['id', 'title', 'price']);
    }

    public function item(Model $model): FeedItem
    {
        return new class($model) extends FeedItem {
            public function toArray(): array
            {
                return [
                    'id' => $this->model->getKey(),
                    'title' => $this->model->title,
                    'price' => (string) $this->model->price,
                ];
            }
        };
    }
}
```

## CSV dialect, JSON document, and JSON Lines

These are sibling classes, so CSV attributes cannot leak into JSON converters.

```php
namespace App\Feeds;

use DragonCode\LaravelFeed\Attributes\Config;
use DragonCode\LaravelFeed\Enums\FeedFormatEnum;

#[Config('delimiter', ',')]
#[Config('escape', '')]
#[Config('lineEnding', "\r\n")]
class SupplierCsvFeed extends ProductExportFeed
{
    protected FeedFormatEnum $format = FeedFormatEnum::Csv;
}

#[Config('pretty', true)]
class CatalogJsonFeed extends ProductExportFeed
{
    protected FeedFormatEnum $format = FeedFormatEnum::Json;
}

#[Config('options', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]
class CatalogLinesFeed extends ProductExportFeed
{
    protected FeedFormatEnum $format = FeedFormatEnum::JsonLines;
}
```

Check two CSV records with the same keys, including a comma and quote in `title`.
Read them with the same delimiter/enclosure/escape settings; check CRLF bytes.
Decode the whole JSON document, but decode each JSONL line independently.
JSONL has no root and must never pretty-print one record across several lines.
For RSS, use a preset with a complete channel envelope and check its XML output.

## XML envelope with metadata before the item container

This variant uses the shared query and items. The metadata stays inside
`inventory`, and the converter closes `entries` before calling `footer()`.

```php
namespace App\Feeds;

use DragonCode\LaravelFeed\Data\ElementData;
use DragonCode\LaravelFeed\Feeds\Info\FeedInfo;

class InventoryXmlFeed extends ProductExportFeed
{
    public function header(): string
    {
        return parent::header() . "\n<inventory version=\"3\">";
    }

    public function root(): ElementData
    {
        return new ElementData(name: 'entries', beforeInfo: false);
    }

    public function info(): FeedInfo
    {
        return new class extends FeedInfo {
            public function toArray(): array
            {
                return ['supplier' => 'North Warehouse'];
            }
        };
    }

    public function footer(): string
    {
        return '</inventory>';
    }
}
```

Parse the complete export and check the paths `/inventory/supplier` and
`/inventory/entries/product`. Check empty output too. To emit a bare JSON array,
override `root()` in a JSON feed with `return new ElementData;` and omit metadata
unless the consumer explicitly accepts it.

## Optional CSV fields retain their position

Inside an item mapping, return a stable key even when its value is absent:

```php
use DragonCode\LaravelFeed\Data\OptionalData;

$row = [
    'id' => 42,
    'discount' => new OptionalData,
    'title' => 'Desk lamp',
];

assert(array_keys($row) === ['id', 'discount', 'title']);
```

The CSV cell is blank, while JSON/XML omit the `discount` field. Do not remove
the key from one CSV record. Test the converter output as well as the item array.
