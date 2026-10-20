# Locale-specific catalog outputs

## Preconditions

The application already has `App\Models\Product` with `id`, `title`, `locale`, and boolean `is_exportable` columns. Each row belongs to one locale. The consumer wants XML with only `id` and `title`, and the existing `public` disk is appropriate for that data. No new package or storage credentials are required.

This design uses two controlled locale keys. For a database-backed set of stores or partners, replace the fixed mapping with lazy discovery and direct eligible-key lookup while keeping the same contract.

## Complete feed

Create `App\Feeds\LocalizedCatalogFeed`:

```php
<?php

declare(strict_types=1);

namespace App\Feeds;

use App\Models\Product;
use DragonCode\LaravelFeed\Concerns\InteractsWithFeedTargets;
use DragonCode\LaravelFeed\Contracts\HasFeedTargets;
use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Feeds\FeedTarget;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class LocalizedCatalogFeed extends Feed implements HasFeedTargets
{
    use InteractsWithFeedTargets;

    private const LOCALES = [
        'en' => 'en_US',
        'de' => 'de_DE',
    ];

    public function targets(): iterable
    {
        foreach (self::LOCALES as $key => $locale) {
            yield new FeedTarget($key, ['locale' => $locale]);
        }
    }

    public function findTarget(string $key): ?FeedTarget
    {
        if (! isset(self::LOCALES[$key])) {
            return null;
        }

        return new FeedTarget($key, ['locale' => self::LOCALES[$key]]);
    }

    public function builder(): Builder
    {
        return Product::query()
            ->select(['id', 'title'])
            ->where('is_exportable', true)
            ->where('locale', $this->target()->parameters['locale']);
    }

    public function item(Model $model): FeedItem
    {
        return new class($model) extends FeedItem
        {
            public function toArray(): array
            {
                return [
                    'id' => $this->model->getKey(),
                    'title' => $this->model->getAttribute('title'),
                ];
            }
        };
    }

    public function filename(): string
    {
        return 'catalog/' . $this->target()->key . '.xml';
    }
}
```

The fixed lookup prevents caller-controlled path fragments from reaching `filename()`. The query retains the shared export eligibility filter before applying locale selection. Explicit item mapping limits output to `id` and `title`, even when the model appends attributes or loads default relationships. Column selection reduces hydration work; it is not an output allowlist.

## Register once and generate

Create one registration in the application's migration or deployment-operation workflow. This complete container call belongs inside that existing reproducible mechanism, not a recurring generation request:

```php
<?php

declare(strict_types=1);

use App\Feeds\LocalizedCatalogFeed;
use DragonCode\LaravelFeed\Queries\FeedQuery;

app(FeedQuery::class)->create(
    class: LocalizedCatalogFeed::class,
    title: 'Localized Catalog',
);
```

Read the actual registration slug with `php artisan feed:list`. When it is `localized-catalog`, these commands generate all locales, one locale, or both selected locales:

```bash
php artisan feed:generate localized-catalog
php artisan feed:generate localized-catalog --target=de
php artisan feed:generate localized-catalog --target=en --target=de
```

Expected disk-relative paths are `catalog/en.xml` and `catalog/de.xml`. With the standard local public disk these are under `storage/app/public/`. Do not use that local prefix to construct remote disk paths.

## Generate one configured target programmatically

Run within a bootstrapped Laravel command or job:

```php
<?php

declare(strict_types=1);

use App\Feeds\LocalizedCatalogFeed;
use DragonCode\LaravelFeed\Services\GeneratorService;

$feed = app(LocalizedCatalogFeed::class);
$target = $feed->findTarget('de')
    ?? throw new RuntimeException('Catalog locale is unavailable.');

$result = app(GeneratorService::class)->feed($feed->forTarget($target));

foreach ($result->records as $path => $records) {
    echo $path . ': ' . $records . PHP_EOL;
}
```

## Verify independence before generation

Run this assertion check in the application's bootstrapped test context with assertions enabled. It performs no generation or database query:

```php
<?php

declare(strict_types=1);

use App\Feeds\LocalizedCatalogFeed;

$feed = app(LocalizedCatalogFeed::class);
$enTarget = $feed->findTarget('en')
    ?? throw new RuntimeException('English target is missing.');
$deTarget = $feed->findTarget('de')
    ?? throw new RuntimeException('German target is missing.');
$english = $feed->forTarget($enTarget);
$german = $feed->forTarget($deTarget);

assert($english !== $feed && $german !== $feed && $english !== $german);
assert($english->filename() === 'catalog/en.xml');
assert($german->filename() === 'catalog/de.xml');
assert($english->target()->parameters === ['locale' => 'en_US']);
assert($german->target()->parameters === ['locale' => 'de_DE']);
assert($feed->findTarget('../private') === null);
assert(array_map(fn ($target) => $target->key, iterator_to_array($feed->targets())) === ['en', 'de']);
```

For an application fixture with two exportable English rows, one exportable German row, and one non-exportable row in each locale, generate and assert counts `2` and `1`, matching IDs, and distinct paths. Also test an unknown explicit key alongside a valid key and verify neither is generated for that request.

## Add parts only when the consumer accepts them

For larger datasets, override `perFile(): int` on this class to return `20000`. Leave `maxFiles()` at `0` for complete output. A locale with `20001` records then produces `catalog/en-1.xml` with `20000` records and `catalog/en-2.xml` with `1`; another locale starts its own part numbering. Verify returned paths instead of assuming the unsuffixed name is published.
