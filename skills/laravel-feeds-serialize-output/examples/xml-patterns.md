# XML patterns

## Attributes, text, CDATA, raw fragments, and repeated elements

Assume a `Product` model with string `sku`, `title`, and `description` fields.
Wire the item into an XML feed with `item(Model $model): FeedItem` returning
`new ProductDetailsItem($model)`.

```php
namespace App\Feeds\Items;

use DragonCode\LaravelFeed\Feeds\Items\FeedItem;

class ProductDetailsItem extends FeedItem
{
    public function name(): string
    {
        return 'offer';
    }

    public function attributes(): array
    {
        return ['id' => $this->model->getKey()];
    }

    public function toArray(): array
    {
        return [
            'title' => $this->model->title,
            'sku' => [
                '@attributes' => ['type' => 'warehouse'],
                '@value' => (string) $this->model->sku,
            ],
            'description' => ['@cdata' => (string) $this->model->description],
            '@image' => ['front.webp', 'side.webp'],
            '@mixed' => '<support code="standard"/>',
        ];
    }
}
```

The fragment is a trusted constant. Keep external text in ordinary values,
attributes, or CDATA instead of interpolating it into `@mixed`. For repeated
images with attributes, replace each image string with an array containing
`@attributes` and `@value`. The directive name is the desired element prefixed
with `@`; there is no required literal `@custom` key.

## Focused parse check

Run this in a bootstrapped Laravel test or application with the package enabled.
It builds one real item fragment without a database query and checks decisions
that a string comparison would miss.

```php
use App\Feeds\Items\ProductDetailsItem;
use DragonCode\LaravelFeed\Converters\XmlConverter;
use Illuminate\Database\Eloquent\Model;

$model = new class extends Model {};
$model->forceFill([
    'id' => 42,
    'sku' => 'A&B',
    'title' => 'Lamp <large>',
    'description' => '<p>Bright & warm</p>',
]);

$xml = app(XmlConverter::class)->item(new ProductDetailsItem($model), true);
$document = new DOMDocument;
assert($document->loadXML($xml));
$xpath = new DOMXPath($document);
assert($xpath->evaluate('string(/offer/sku)') === 'A&B');
assert($xpath->evaluate('string(/offer/sku/@type)') === 'warehouse');
assert($xpath->evaluate('string(/offer/title)') === 'Lamp <large>');
assert($xpath->evaluate('string(/offer/description)') === '<p>Bright & warm</p>');
assert($xpath->evaluate('count(/offer/image)') === 2.0);
assert($xpath->evaluate('string(/offer/support/@code)') === 'standard');
```

Enable assertions when running assert-based checks. Add a malformed-fragment
case that requires `InvalidXmlFragmentException`. For a `]]>` description, parse
and compare the complete text round trip rather than assuming the terminator
must fail. Separately parse the complete generated file to check the envelope
and metadata placement.
