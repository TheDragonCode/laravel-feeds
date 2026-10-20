# Operational patterns

## Discover before choosing scope

For an existing registration with slug `inventory`, run the selected command after inspecting its state:

```bash
php artisan feed:list
php artisan feed:generate inventory
```

The second command deliberately executes an inactive registration too. Use `php artisan feed:generate` only when the requested scope is all active registrations. A deleted registration can appear in the listing but cannot be selected through normal slug lookup.

An agent success response can look like this; paths are illustrative adapter results:

```json
{"tool":"feed:generate","result":"success","feeds":[{"class":"App\\Feeds\\InventoryFeed","status":"generated","files":[{"path":"/srv/app/storage/app/public/inventory-1.xml","records":25000},{"path":"/srv/app/storage/app/public/inventory-2.xml","records":3200}]}]}
```

Validate both parts and the total `28200`. A `queued` entry contains no `files`; do not invent counts from it.

## Configure an asynchronous worker

When the application already has a Redis queue connection, these environment settings select a dedicated queue:

```dotenv
FEED_QUEUE_ENABLED=true
FEED_QUEUE_CONNECTION=redis
FEED_QUEUE_NAME=exports
FEED_QUEUE_UNIQUE_TTL=7200
```

Start a worker with the application's measured timeout and memory budget. A minimal connection/queue command is:

```bash
php artisan queue:work redis --queue=exports
```

Verify actual worker completion from the normal asynchronous path. A recognized agent runs the job synchronously, so that path cannot prove Redis worker delivery. Do not change credentials or flush uniqueness locks merely to make the check pass.

## Register database schedules

Use this as the complete scheduler integration in `routes/console.php` when persisted expressions own feed timing:

```php
<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Helpers\ScheduleFeedHelper;

ScheduleFeedHelper::register();
```

For one existing registration with slug `inventory`, this is an alternative manual schedule. Do not also register that feed through the automatic helper:

```php
<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Commands\FeedGenerateCommand;
use Illuminate\Support\Facades\Schedule;

Schedule::command(FeedGenerateCommand::class, ['inventory'])
    ->withoutOverlapping()
    ->runInBackground()
    ->weekdays()
    ->at('04:30');
```

To assign a stored expression when creating a registration in an application migration or deployment operation, the container call is:

```php
<?php

declare(strict_types=1);

use App\Feeds\InventoryFeed;
use DragonCode\LaravelFeed\Queries\FeedQuery;
use DragonCode\LaravelFeed\Scheduling\Expression;

app(FeedQuery::class)->create(
    class: InventoryFeed::class,
    title: 'Inventory',
    expression: (new Expression)->weekdays()->at('04:30'),
);
```

Run this once through that reproducible registration mechanism. Do not create another row each time generation runs.

## Consume all service results

Run this within a bootstrapped Laravel command or job. It assumes the existing `App\Feeds\InventoryFeed` definition:

```php
<?php

declare(strict_types=1);

use App\Feeds\InventoryFeed;
use DragonCode\LaravelFeed\Services\GeneratorService;
use Illuminate\Support\Facades\Log;

$result = app(GeneratorService::class)->feed(app(InventoryFeed::class));

foreach ($result->records as $path => $records) {
    Log::info('Inventory output published.', [
        'path' => $path,
        'records' => $records,
    ]);
}
```

Use a listener for publication integration. This application listener records every path and target without assuming a single file:

```php
<?php

declare(strict_types=1);

namespace App\Listeners;

use DragonCode\LaravelFeed\Events\FeedFinishedEvent;
use Illuminate\Support\Facades\Log;

final class LogPublishedFeed
{
    public function handle(FeedFinishedEvent $event): void
    {
        Log::info('Feed publication completed.', [
            'feed' => $event->feed,
            'target' => $event->target,
            'paths' => $event->paths,
        ]);
    }
}
```

Register the listener through the application's normal event discovery or listener registration convention. `FeedStartingEvent` is unsuitable as evidence that a file exists.

## Keep tuning bounded by the consumer contract

This complete definition assumes `App\Models\Product` with `id`, `title`, and `is_exportable`. It selects required columns, maps only the consumer fields, uses smaller chunks, and produces unlimited record-count parts:

```php
<?php

declare(strict_types=1);

namespace App\Feeds;

use App\Models\Product;
use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class InventoryFeed extends Feed
{
    public function builder(): Builder
    {
        return Product::query()
            ->select(['id', 'title'])
            ->where('is_exportable', true);
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

    public function chunkSize(): int
    {
        return 250;
    }

    public function perFile(): int
    {
        return 25000;
    }
}
```

Leave `maxFiles()` at its inherited `0` when all records are required. On the same fixture, compare exported counts before and after tuning, confirm each part has at most `25000` rows, and measure peak memory, query count, total time, and publication time separately. The numeric choices are starting points, not measured recommendations for another application.
