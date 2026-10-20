# Preset contracts

## Choose the output contract

All presets extend `DragonCode\LaravelFeed\Feeds\Feed`. Import presets from `DragonCode\LaravelFeed\Presets`, `FeedItem` from `DragonCode\LaravelFeed\Feeds\Items`, and `FeedInfo` from `DragonCode\LaravelFeed\Feeds\Info`.

| Preset | Root and wrapper | Item and required mapping | Defaults to preserve |
| --- | --- | --- | --- |
| `SitemapFeedPreset` | XML declaration, `urlset` with sitemap and extension namespaces | `url`: `url()`, producing `loc`; non-null Carbon modification date | Parent item reads `updated_at`; `priority` is `0.9`; `perFile()` is `50000` |
| `InstagramFeedPreset` | RSS 2.0 wrapper, `xmlns:g="http://base.google.com/ns/1.0"`, channel title/link, then `offers` | `item`: title, description, URL, primary image, price | ID from model key; condition `new`, availability `in stock`, status `active`; sale price equals price unless supplied |
| `YandexFeedPreset` | `yml_catalog` doctype and dated wrapper, `shop`; info precedes `offers` | `offer`: URL, title, description, price, images | ID from model key, available `true`, type `vendor.model`, currency `RUB` |
| `RssFeedPreset` | XML declaration, RSS 2.0 wrapper with `dc` and `atom` namespaces, `channel` | `item`: title, URL, description | GUID from model key; publication time from `created_at`, falling back to current time; RSS format gives `.rss` unless filename is overridden |

Do not treat the RSS preset as an Atom document generator. Its item vocabulary is RSS, including `guid`, `description`, and `pubDate`.

## Sitemap

- `url(string)`, `modifiedAt(Carbon)`, and `priority(float)` define `loc`, ISO 8601 `lastmod`, and priority.
- `parent::item()` immediately reads `updated_at`. A null timestamp must be handled in the query/model or by constructing an appropriate item directly; a later setter cannot undo a type error in the parent call.
- Keep `http://www.sitemaps.org/schemas/sitemap/0.9` as the default namespace. Preset extension namespaces cover XHTML, images, video, and news.
- Return a relative destination such as `sitemaps/products.xml` from `filename()`. Inspect actual split filenames when more than 50,000 rows are exported; do not assume the unsuffixed file exists.
- For a public disk, `php artisan storage:link` uses the application's existing filesystem link configuration. If a root sitemap index is requested, list actual public URLs of generated files in `sitemapindex`, rather than inserting a `urlset` inside it.

## Instagram catalog

- `title(string)`, `description(string)`, `url(string)`, `image(string)`, and `price(float, ?float)` are required to initialize item fields. Ensure a primary image exists before mapping it; avoid blindly reading array index zero.
- `images(?array)` produces repeated additional-image elements. It does not choose the required primary image.
- Optional setters include `brand(string)`, `condition(string)`, `availability(string)`, `status(?string)`, `group(int|string|null)`, `googleCategory(?int)`, and `facebookCategory(?int)`.
- Preserve `g:` prefixes for added catalog elements. `additional(array)` uses XML directives for attributes, CDATA, and repeated elements; it replaces the previous additional array, so combine extras once.
- The preset obtains channel title/link from `app.name` and `app.url` and escapes XML text. Keep safe escaping when customizing a header.
- Check emitted prices and required receiver fields against the service contract. Preset serialization is not a complete catalog validator.

## Yandex catalog

- `info()` returns a `YandexFeedInfo`. `name()`, `company()`, `platform()`, and `url()` fall back to application settings. Set a contact email explicitly when required.
- `currencies(['RUB' => 1])` replaces the currency list. `currency(name, rate, replace: false)` appends; currency IDs are uppercased. Every offer's `currencyId()` must identify an emitted currency.
- `categories([id => name])` replaces categories. `category(id, name)` appends a normal category. `rawCategory(array)` appends an unchanged XML directive array; use it for `parentId` and custom fields. Arrays inside `categories()` are also treated as raw structures.
- Raw category arrays are not validated or transformed by the info object. Express attributes and text using `@attributes` and `@value`; let the converter escape values. Ensure category IDs are unique and parent IDs exist.
- `attributeId()`, `attributeAvailable(bool)`, and `attributeType(string)` set offer attributes. `barcode(string)`, `vendor(?string)`, `currencyId(string)`, and `additional(array)` add optional mapping. `price()` accepts float or string; images must be an array.
- There is no preset `categoryId()` setter. Put an offer's category ID in `additional(['categoryId' => $id])`. Use the relationship's key, and ensure the same ID is present in shop metadata.

## RSS metadata and relations

- The default `info()` is empty. Supply a `FeedInfo` subclass with `toArray()` when channel metadata such as title, link, and description is needed.
- `guid(int|string)` stringifies the identity; keep it stable across regenerations. `publishedAt(Carbon)` emits RFC 1123 dates; preserve original publication time when that is the receiver's meaning.
- `category(string)` requires text. For an Eloquent category relationship, eager-load it and pass its name; omit the setter when the relationship is absent.
- The description is emitted as CDATA. Additional values use XML directives; avoid assembling unescaped XML strings.

## Activation and acceptance checks

`make:feed` creates a feed definition and registration migration, or a deploy operation when that integration is installed. Presets provide their own item types, so separate generated item/info classes are optional. Review registration title, class, schedule, active state, and rollback before applying it.

Run `php artisan migrate` for migrations or `php artisan operations` for deploy operations. Then inspect the stored registration slug and run `php artisan feed:generate <slug>`. Omitting the slug processes all active registrations and may generate unrelated files.

Verify XML well-formedness without network-dependent DTD fetching, wrapper/root order, namespaces, required fields, URL visibility, declared currencies/category references, representative records, row counts, and split-file names. Use the receiver's validator or ingestion check when available and requested; report that separately from local checks.
