# Target implementation contract

## Choose the mechanism

| Requested result | Mechanism |
| --- | --- |
| Different filtered dataset for each store, locale, partner, or business condition | `HasFeedTargets` and `FeedTarget`. |
| Consecutive files from one dataset with a fixed row count | `perFile()`; add `maxFiles()` only for an intentional record cap. |
| Business-specific datasets that each need multiple parts | Targets plus `perFile()`. |

One registration selects the feed class. A target selects one execution of that class. Record-count splitting happens after that target's query selection. Changing `perFile()` cannot create business-specific filters.

## Required APIs

Import `DragonCode\LaravelFeed\Contracts\HasFeedTargets`, `DragonCode\LaravelFeed\Concerns\InteractsWithFeedTargets`, and `DragonCode\LaravelFeed\Feeds\FeedTarget`.

| Method | Responsibility |
| --- | --- |
| `targets(): iterable` | Lazily yield `FeedTarget` objects for every available output. Avoid loading the full target collection for large discovery sets. |
| `findTarget(string $key): ?FeedTarget` | Direct lookup with the same eligibility rules and parameter shape as discovery; return `null` for unknown keys. |
| `forTarget(FeedTarget $target): static` | Trait implementation clones the definition, sets the snapshot, and clears the cached filename. |
| `target(): FeedTarget` | Trait implementation returns the configured target or throws `LogicException` if unset. |

`new FeedTarget(key: $key, parameters: $parameters)` rejects blank keys. It does not normalize or sanitize a key. The application owns key uniqueness, stability, allowed formats, and safe disclosure. Keys occur in console/agent output, lifecycle events, and failures.

Parameters are stored as a queued-job snapshot. Keep them small, serializable, and free of secrets. Use identifiers and scalar conditions instead of hydrated models. Snapshot parameters reflect discovery/selection time; querying uses those conditions at execution time.

## Query and path invariants

- Keep ordinary eligibility filters while adding the target-specific filter.
- Return `Illuminate\Database\Eloquent\Builder`, not a materialized dataset.
- Each target must resolve to its own deterministic filename. A later target can overwrite an earlier target when paths collide.
- Put a key in a path only when its format is application-controlled. Treat externally supplied keys as lookup inputs, not filenames.
- Do not mutate a shared feed instance between targets. Use the clone from `forTarget()`.
- Keep disk-relative output paths in `filename()`; `path()` is adapter-resolved and is not a public URL.

## Selection and execution

`php artisan feed:generate inventory` selects the registration and streams every target. An empty iterable generates nothing; there is no implicit ordinary execution. Discovery is not atomic: if it fails after yielding targets, earlier outputs or queue jobs may already exist.

`php artisan feed:generate inventory --target=en --target=de` selects explicit keys through `findTarget()`, without calling `targets()`. Duplicates run once in first-seen order. Every requested key resolves before any selected generation starts. Missing slug, blank keys, or a non-targeted feed raise `InvalidArgumentException`; unknown targets raise `UnexpectedValueException`.

Scheduled execution enumerates all targets. Normal queue mode creates one job per target, storing its snapshot. Target uniqueness uses feed class and key, regardless of parameters; changing the snapshot does not create a new uniqueness identity. Recognized agent queue execution is synchronous, even though its JSON status says `queued`.

`GeneratorService::feed()` accepts one configured feed and does not enumerate targets. Call `findTarget()`, handle a missing result, then pass `$feed->forTarget($target)` to the container-resolved service.

## Splitting and recovery

Both `perFile()` and `maxFiles()` default to `0`. With `perFile() > 0`, `catalog/en.xml` becomes `catalog/en-1.xml`, `catalog/en-2.xml`, and so on. The split starts independently for every target. Positive `maxFiles()` caps rows per target at `perFile() * maxFiles()` without reporting later omitted rows as a failure. Leave the cap disabled for complete exports.

Targeted results, lifecycle events, and `FeedGenerationException::getFeedTarget()` retain the selected key. Results list all paths and record counts. `FeedFinishedEvent::$path` covers only the first part; use `$paths` for integration. Targeted preflight failures are wrapped as well as generation failures, with the original cause available through `getPrevious()`.

## Checks before completion

1. Discovery and lookup agree on valid keys, eligibility, and parameters.
2. Unconfigured access fails instead of accidentally producing an unfiltered feed.
3. Configuring another target leaves the original definition and previous clone unchanged.
4. All filenames differ, remain deterministic, and use controlled path components.
5. Actual output records satisfy target conditions and preserve shared filters.
6. Selected keys bypass enumeration; unknown explicit keys prevent generation for the selected request.
7. Each target's split counts sum to its expected dataset size unless an intentional cap is documented.
