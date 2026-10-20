# Upgrade rules

## Compatibility and inventory

The 2.x contract requires PHP `^8.4` and Laravel components `^13.0`. The corresponding 1.x contract allows PHP `^8.2` and Laravel components `^11.0 || ^12.0 || ^13.0`. Verify the intended release metadata and resolved dependency graph before changing an application; do not infer compatibility from a different release or its documentation.

Check web, CLI, scheduler, and queue runtimes individually. Run Composer's platform check in each deployment environment. PHP extension requirements and all transitive constraints remain part of the installed package contract. Pest 5 and Testbench 11 are package-development dependencies, not a mandate to replace an application's test stack.

Inventory registrations with IDs, actual titles and slugs, classes, active state, deletion state, schedule expressions, disks, filenames, and targets. Find every `feed:generate` invocation in cron, deployment, tests, `Artisan::call`, and manual scheduler code. Record baseline output and split-file counts before upgrading.

## Dependencies and configuration

Preserve application code, `composer.json`, `composer.lock`, published `config/feeds.php`, registration-table data, and rollback-required output files. Pause schedules and finish queued/in-flight feed jobs before replacing code or schema.

Use `composer require dragon-code/laravel-feeds:^2.0 --with-all-dependencies`, review the resolved changes, and run `composer check-platform-reqs`. Compare configuration defaults with the published application copy and merge required additions. `vendor:publish --force` can overwrite application settings and is not an upgrade merge strategy.

## Slug migration

1. Inspect migration history and the current schema. Later 1.x versions may already contain slug support; skip completed steps.
2. If required, `php artisan vendor:publish --tag=feeds-migrations` publishes all migration files. Retain only files the application needs. Do not add a second table-creation migration merely because it was published.
3. Order `add_slug_to_feeds_table` after the application's actual `create_feeds_table` migration and before every migration that creates feed registrations. Both existing installations and isolated `migrate:fresh` replays must work.
4. If an application-owned slug column already exists but the package migration has not run, resolve the conflict before deployment. The package migration tries to add the column first and fails before backfill. Preserve the existing column's data and callers.
5. Apply through the application's deployment migration policy. The package adds nullable `slug` values and backfills `Str::slug(title)` for all rows, including soft-deleted registrations. It preserves IDs and titles.

Do not write a second migration that silently repeats the backfill or drops an existing column to make publication succeed.

## Slug identity and callers

- Every selectable slug must be non-empty and unique among non-deleted registrations, including inactive ones. Distinct titles can normalize to the same slug; the schema does not guarantee a collision-free result.
- Unknown slugs throw `FeedNotFoundException`; ambiguous slugs throw `MultipleRecordsFoundException`.
- Saving a feed model recalculates the slug from its stored title. Resolve collisions by editing titles through the configured feed model and update dependent callers. Bulk Query Builder writes bypass model events and must be reviewed separately.
- Custom feed models must retain compatible slug casts/events. Inspect overridden casts and lifecycle methods.
- In 2.x, the optional `feed:generate` registration argument is always a slug. A numeric string is a numeric slug, never a database-ID lookup. With no argument, generation still processes active registrations.
- `--target` keys keep their original meaning, including numeric keys. Replace the registration argument only.
- `FeedQuery::find(int $id)`, `delete(int $id)`, and `restore(int $id)` remain ID-based. `findBySlug(string $slug)` performs slug selection.
- Preserve existing manual schedule frequency, overlap locks, and background settings. `ScheduleFeedHelper::register()` reads stored slugs automatically and keeps automatic overlap locks tied to the registration across title or cron changes.

## New titles versus existing data

New `make:feed ProductsFeed` registrations use title `Products` and derived slug `products`, where older generation produced `Products Feed` and `products-feed`. Existing rows and previously generated registration files are not rewritten by this change. Keep their actual stored slug unless explicitly migrating their title. Definition class names and output filenames are unaffected.

Version-specific documentation and tooling should match the installed package. Documentation route changes and package-development test flags do not require application runtime changes.

## Verification and rollback

On an isolated database, verify both upgrading representative existing rows and replaying the full migration history. Include an inactive registration, a soft-deleted row, colliding normalized titles, and an empty normalized title in migration checks. Test the application's conflict-resolution process rather than accepting ambiguous slugs.

Generate at least one feed per used format; compare filenames, record data, split counts, target isolation, and destinations with the baseline. Test used custom models, transformers, macros, and preset subclasses. Exercise automatic/manual scheduling and queued generation when enabled. Rebuild configuration caches if used; restart workers and long-running scheduler processes with the new code before resuming schedules.

For rollback, pause and drain generation again. Restore code, dependency manifests/lockfile, configuration, and caller arguments; run `composer install` on a compatible runtime. Follow the application's migration policy only after checking whether the restored 1.x release already uses slugs. The slug migration's `down()` drops the slug column; it does not revert renamed titles or generated output. Restore those from backups when needed, refresh caches, restart long-running processes, and verify generation before resuming schedules.
