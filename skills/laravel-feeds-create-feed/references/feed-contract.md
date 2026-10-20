# Feed Construction Contract

## Installation and Registration

- Use Composer's installed dependency graph to decide compatibility. The documented generation accepts PHP `8.4` and later supported `8.x` releases with Laravel components `13.x`; the package also requires DOM and libxml extensions. Do not assume another installed release has identical constraints.
- Publish with `php artisan vendor:publish --tag="feeds"`. Inspect the database connection and table in `config/feeds.php` before running migrations.
- `make:feed Product --item` creates a feed, item, and registration artifact. `--info` adds feed-level metadata. `-t` and `-i` are their short options. Use `--force` only when overwriting the existing classes is intended.
- With Laravel Deploy Operations installed, scaffolding creates an operation; otherwise it creates a migration. Apply the actual artifact using the application's deployment workflow. A second `migrate` can be required when scaffolding follows initial package migrations.
- On upgrades that lack the slug column, apply the installed package's slug upgrade migration after the actual feed-table creation and before registration migrations. Do not rerun table creation.
- A feed class and a registration record serve different roles. Defining the class alone does not register it for command discovery.

## Data Source and Item Schema

| API | Required behavior |
| --- | --- |
| `Feed::builder(): Builder` | Return a lazy Eloquent query; never a collection or query-builder-only instance. |
| `Feed::item(Model $model): FeedItem` | Return an item wrapping the current model. |
| `FeedItem::toArray(): array` | Return the consumer's explicit field names and values. |
| `Feed::chunkSize(): int` | Positive records per database chunk; defaults to `1000`. |

- The base `FeedItem::toArray()` returns the model array. Override it when export fields need a stable schema or must exclude internal attributes and loaded relationships.
- Select the model primary key when using the package's default ID-based lazy traversal. Select foreign keys and related primary keys required for eager-loaded relationships.
- Eager-load the exact relations read by item mapping. Accessing a relationship for every row without eager loading causes N+1 queries. Avoid loading unrelated relations.
- Keep filtering in `builder()`. Do not materialize every candidate before deciding which rows to export.
- Lower chunk sizes trade more queries for less model memory. Higher sizes trade fewer queries for more memory. Preserve defaults until representative measurements justify a change.
- A returned builder does not by itself prove bounded memory: item code, relation payloads, transformations, and storage also contribute. Check more than one chunk with realistic rows.
- Queueing changes execution location; it does not reduce per-feed memory. File splitting, record caps, queue setup, and publication diagnostics belong to the generation workflow.

## Missing Fields and Dates

- Return `new DragonCode\LaravelFeed\Data\OptionalData` to omit an unavailable field. Normal nested fields are omitted from JSON/JSON Lines objects and XML/RSS elements; CSV retains its column and writes an empty field.
- If Spatie Laravel Data is already installed, its optional values are also accepted. Do not add it solely to represent an absent field.
- Use `DragonCode\LaravelFeed\Attributes\DateFormat` on an item class. It is non-repeatable and uses PHP date tokens.
- Return raw `DateTimeInterface` objects, such as Eloquent date-cast attributes. Calling `$model->toArray()` first can turn them into strings that cannot be reformatted.
- The nearest annotated parent provides the inherited item format; a child annotation overrides it. The item format takes precedence over feed-specific and global date formats while preserving `feeds.date.timezone`. Later string transformers may still change the value.
- The item attribute also applies to nested item dates and XML/RSS item attributes. It does not change `FeedInfo`, root attributes, or unrelated item classes. The normal default date format is `DATE_ATOM`, with `UTC` as the configured default timezone.

## Format and Destination Choice

- `protected FeedFormatEnum $format` defaults to `FeedFormatEnum::Xml`. Available cases are `Xml`, `Json`, `JsonLines`, `Csv`, and `Rss`.
- Choose a format the consumer accepts before designing its schema. XML/RSS structure, directives, CSV scalar columns, JSON encoding flags, and metadata layout need format-specific serialization work.
- `DragonCode\LaravelFeed\Attributes\Config` is a repeatable feed-class attribute. It is distinct from Laravel's container attribute of the same short name. Its names are converter constructor parameters, such as `lineEnding`, rather than config paths or snake-case keys. Validate supported parameters for the selected format; unknown names throw `InvalidArgumentException`.
- `protected string $storage` defaults to `public`. Check that exported fields may be exposed on the chosen disk. Use an existing writable disk configured for the application's access requirements.
- Supported disks resolve to `Illuminate\Filesystem\FilesystemAdapter`; a generic filesystem-contract implementation is insufficient. Do not select a read-only disk.
- `filename()` returns a disk-relative filename. The default derives from the feed class namespace/name and selected extension. Override only when the consumer needs a fixed path, and keep its extension consistent with the format.
- `storagePath()` is the disk-relative path for storage operations. `path()` is adapter-resolved and is not a portable remote public URL.

## Custom Registration Model

- `feeds.model` must extend `DragonCode\LaravelFeed\Models\Feed`. Preserve the base fillable fields and merge `parent::casts()` when adding custom casts; the package relies on class, expression, slug, active, and date casts.
- Add custom fields to the configured registration table in an application migration before creating records using those fields. Keep every mass-assigned custom field in `$fillable`.
- `FeedQuery` uses the configured model for create/find/list/update/delete/restore. Its `create()` accepts `class`, `title`, `expression`, `isActive`, and `extra`.
- `extra` is applied last and can override even standard arguments. Pass controlled application attributes; do not forward unvalidated request data.
- Static analysis sees `FeedQuery::create()` as the package base model. Use the application model API when static access to custom properties is required.
- Model saves derive `slug` from the final stored title, including custom title setters and `saveQuietly()`. Renaming a title changes the slug. Bulk query-builder updates/inserts bypass this behavior.
- Slugs must be distinct and non-empty for selected or scheduled feeds. Normalization does not add numeric suffixes. Duplicate non-deleted registrations with one slug cause `MultipleRecordsFoundException` during lookup; an empty normalized slug cannot be selected.

## Completion Checks

| Failure signal | Check or correction |
| --- | --- |
| Feed class exists but cannot be discovered | Apply its generated registration artifact and inspect the configured connection/table. |
| Memory grows with the full dataset | Remove eager materialization; inspect item/relationship payloads; measure across chunk boundaries. |
| Missing rows or lazy traversal errors after selecting columns | Include the primary key and preserve intended query filters/order/limits. |
| A query for every item relation | Add constrained eager loading with the required relation keys. |
| Dates ignore the item format | Return date-cast objects instead of already serialized strings. |
| Custom model loses registration behavior | Merge parent casts, preserve base fillable fields, and check `feeds.model`. |
| Slug selection fails after registration/title change | Inspect the stored slug and title normalization; remove duplicate intent through model-aware changes. |
| Output exposes fields absent from the consumer schema | Replace model-wide output with an explicit item mapping and recheck disk visibility. |
