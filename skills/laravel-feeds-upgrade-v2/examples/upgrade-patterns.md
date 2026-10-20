# Upgrade patterns

## Dependency and migration preparation

After confirming the target runtime and preserving rollback artifacts:

```bash
composer require dragon-code/laravel-feeds:^2.0 --with-all-dependencies
composer check-platform-reqs
php artisan migrate:status
```

If slug support is absent, publish migration files, then inspect them before running migrations:

```bash
php artisan vendor:publish --tag=feeds-migrations
```

For an application whose table creation is dated `2025_03_01_000000` and first registration is dated `2025_03_02_000000`, give the new slug migration a timestamp between those two. Keep the original table-creation migration and omit a newly published duplicate. A successful upgrade of the current database does not establish that a clean migration replay is ordered correctly.

Run `php artisan migrate` only after resolving the published file set, existing-column conflicts, ordering, and the application's deployment conditions. Use an isolated test database for a full replay.

## Inspect and validate stored slugs

Run this in a bootstrapped application, such as `php artisan tinker`. It includes inactive registrations and separately lists trashed rows:

```php
<?php

use DragonCode\LaravelFeed\Queries\FeedQuery;

$query = app(FeedQuery::class);
$registrations = $query->all()->get(['id', 'title', 'slug', 'is_active']);
$empty = $registrations->filter(fn ($feed) => blank($feed->slug));
$duplicates = $registrations
    ->groupBy('slug')
    ->filter(fn ($group) => $group->count() > 1);
$includingDeleted = $query->all(true)->get(['id', 'title', 'slug', 'deleted_at']);

if ($empty->isNotEmpty() || $duplicates->isNotEmpty()) {
    throw new RuntimeException('Resolve empty or duplicate registration slugs before resuming feeds.');
}
```

Inspect `$registrations` and `$includingDeleted` to record identities and confirm the backfill. The duplicate check intentionally uses all non-deleted rows, including inactive ones. Keep the deletion state intact.

If titles `Seasonal Stock` and `Seasonal-Stock` collide, choose a distinct title for the intended registration through the configured model and update its callers. Do not bulk-update only `slug`: a later model save derives it from title again.

## Registration ID changes, numeric targets do not

Suppose registration ID `73` has stored title `Seasonal Stock` and migrated slug `seasonal-stock`. Replace this old invocation:

```bash
php artisan feed:generate 73 --target=9
```

With:

```bash
php artisan feed:generate seasonal-stock --target=9
```

`9` remains a target key. It is not a registration ID. Omitting the registration still means all active registrations, and cannot be used with a target filter.

Update a manual schedule's argument while preserving the existing execution settings:

```php
<?php

use DragonCode\LaravelFeed\Commands\FeedGenerateCommand;
use Illuminate\Support\Facades\Schedule;

Schedule::command(FeedGenerateCommand::class, ['seasonal-stock'])
    ->dailyAt('02:30')
    ->withoutOverlapping()
    ->runInBackground();
```

Programmatic command calls must use the same stored slug:

```php
<?php

use Illuminate\Support\Facades\Artisan;

Artisan::call('feed:generate', [
    'feed' => 'seasonal-stock',
    '--target' => ['9'],
]);
```

Existing ID-based management calls stay ID-based:

```php
<?php

use DragonCode\LaravelFeed\Queries\FeedQuery;

$query = app(FeedQuery::class);
$sameRegistration = $query->find(73);
$selectedBySlug = $query->findBySlug('seasonal-stock');

assert($sameRegistration->getKey() === $selectedBySlug->getKey());
```

## Title-generation decision

| Observed state | Correct action |
| --- | --- |
| Existing row titled `Seasonal Stock Feed`, slug `seasonal-stock-feed` | Keep that slug in callers unless a deliberate title migration is requested |
| New registration generated for `SeasonalStockFeed`, title `Seasonal Stock` | Read and use its stored slug `seasonal-stock` |
| Slug migration and updated title generation already exist in 1.x | Verify current data and skip completed work |
| Existing custom slug column conflicts with an unapplied package migration | Preserve data and resolve the schema/caller contract before deployment |

## Deployment and rollback rehearsal

Rehearse on a copy of representative registration data: pause schedules, drain feed jobs, apply the reviewed dependency/schema/caller changes, run slug checks, and compare each used output format with baseline files. Check split counts and names, target filtering, custom extensions, and queued completion. Refresh configuration caches and restart long-running processes before resuming generation.

Rehearse restoring the previous code and lockfile with `composer install`. Restore prior command arguments and any renamed registration titles. Keep the slug column if the restored 1.x release requires it; rolling that migration down is not automatically correct. Restore output files from the preserved copies when the upgrade changed them.
