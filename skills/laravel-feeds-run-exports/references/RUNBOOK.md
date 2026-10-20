# Runtime contract

## Select and inspect registrations

| Command | Behavior |
| --- | --- |
| `php artisan feed:list` | Read-only listing, including inactive and soft-deleted registrations; no custom arguments or options. |
| `php artisan feed:generate` | Visits non-deleted registrations, generates active feeds, and reports inactive feeds as skipped. |
| `php artisan feed:generate inventory` | Resolves one non-deleted registration slug and runs it even when inactive. |
| `php artisan feed:generate inventory --target=en --target=de` | Runs selected keys on one targeted registration. |

Use a registration slug, not a class name or target key, as the positional argument. Missing registrations throw `FeedNotFoundException`; ambiguous slugs throw `Illuminate\Database\MultipleRecordsFoundException`. Invalid slugs throw `InvalidFeedArgumentException`.

The listing contains `id`, `slug`, `title`, `expression`, `is_active`, `is_deleted`, and nullable ISO 8601 `last_activity`. Non-deleted rows precede deleted rows; each group puts active rows first, then sorts by title and ID. Empty human output says `No feeds available`.

Target selection requires one slug and a `HasFeedTargets` implementation. Empty keys and unsupported selection throw `InvalidArgumentException`; unknown keys throw `UnexpectedValueException`. Duplicate keys run once in first-seen order, and every explicit key resolves before generation. Full enumeration is streamed: earlier targets may have completed before a later discovery failure. Empty enumeration runs nothing.

## Automatic agent output

Agent recognition selects compact single-object JSON automatically. Do not pass an invented `--agent` option.

| Command | Success envelope |
| --- | --- |
| `feed:list` | `{"tool":"feed:list","result":"success","feeds":[...]}` |
| `feed:generate` | `{"tool":"feed:generate","result":"success","feeds":[...]}` |

Listing booleans remain JSON booleans; an empty list is `"feeds":[]`. Generation entries contain `class` and `status`; targeted generated/queued entries also contain `target`.

| Generation status | Evidence |
| --- | --- |
| `generated` | Includes `files`, each with `path` and `records`, for every published file. |
| `queued` | Agent-aware queue jobs have run synchronously before success returns; no file list is included. |
| `skipped` | Inactive registration during a run without a slug. |

An exception uses Laravel's normal command failure reporting. There is no JSON error envelope. Check the exit status before decoding output as a success object.

## Queue and schedule

Direct console generation is the default. `FEED_QUEUE_ENABLED=true` enables jobs. `FEED_QUEUE_CONNECTION` falls back to `QUEUE_CONNECTION`, then `database`; `FEED_QUEUE_NAME` defaults to the connection's queue. `sync` executes immediately. A normal human path on an asynchronous connection dispatches and returns; a recognized agent uses synchronous dispatch even when the configured connection is asynchronous.

One job represents one ordinary feed or one selected target. Ordinary uniqueness uses the class; targeted uniqueness uses class and key. Changing target parameters does not create a different uniqueness identity. `FEED_QUEUE_UNIQUE_TTL` is in seconds, defaults to `3600`, and should exceed expected generation time. Check workers, timeout, memory, connection, queue, and locks when dispatch succeeds without output.

`ScheduleFeedHelper::register()` registers active rows using stored cron expressions and overlap locks. It safely skips registration if the configured table does not exist. `feeds.schedule.background` controls background execution; `feeds.schedule.ttl` is an overlap-lock TTL in minutes. A scheduled targeted feed enumerates all targets. The host still needs Laravel's scheduler to run.

Registration expressions accept raw cron strings or `DragonCode\LaravelFeed\Scheduling\Expression`. The fluent value object persists a stable five-field cron expression. Sub-minute scheduling, timezone/time-window constraints, and `lastDayOfMonth()` are unavailable on that value object. Put richer scheduling rules on Laravel's scheduler directly. Prefer one scheduling approach per registration to avoid duplicate execution.

## Service, results, and lifecycle

Resolve `DragonCode\LaravelFeed\Services\GeneratorService` through `app()`. Its `feed(Feed $feed, ?OutputStyle $output = null): GenerationResultData` generates one configured execution. It does not select a registration or enumerate targets.

The result's `paths` contains ordered published paths; `records` maps each path to its record count. Successful generation updates registration activity for the feed class.

| Event | Data and timing |
| --- | --- |
| `FeedStartingEvent` | `feed`, nullable `target`; after storage/path preflight, before converter resolution and staging. It does not prove a draft exists. |
| `FeedFinishedEvent` | `feed`, nullable `target`, `paths`, compatibility `path`; after successful publication of all parts. `path` is the first entry only. |

An ordinary feed's storage/path preflight errors escape directly. Configured targeted preflight failures are wrapped in `FeedGenerationException` to retain the key. Post-preflight failures are wrapped for both kinds of feed. Inspect `getFeed()`, nullable `getFeedTarget()`, and `getPrevious()`. Catch only where recovery or useful application context can be added.

## Storage paths and publication

The default disk is `public`. The default filename derives from the feed class below the application's `Feeds` namespace, converted to kebab case, with the format extension. Override protected `$storage` or `filename()` only when the consumer contract requires it; make target filenames distinct.

| API | Meaning |
| --- | --- |
| `storage()` | Configured Laravel `FilesystemAdapter`. |
| `storagePath($suffix = '')` | Disk-relative filename, with optional part suffix before the extension. |
| `path($suffix = '')` | Adapter-resolved path: absolute on local storage, adapter-specific remotely. It is not necessarily a public URL. |

Supported disks must resolve to `Illuminate\Filesystem\FilesystemAdapter` and support writable streams, moves, directory management/listing, and deletion. Local, S3, SFTP, and FTP are usable when configured with their required adapters. An implementation of only the filesystem contract fails before the query with `UnsupportedStorageDiskException`. A read-only Laravel disk passes the type check, then fails publication after query and staging; the failure is wrapped.

All parts are staged completely before publication. Local publication uses filesystem moves under an exclusive host-local lock and attempts to restore previous files if a move fails. Remote publication uploads complete staged files before moving objects into place and also attempts rollback. Copy-and-delete moves and eventual consistency mean remote atomic replacement is not guaranteed, including across split parts. Concurrent publishers on different hosts need an application-provided distributed lock for the same output.

`.feeds_staging_*` directories next to outputs normally disappear after successful publication or ordinary generation failures. Failed rollback preserves backups. Process interruption and cleanup errors can also leave staging behind. Review original errors and preserved backups before any requested manual cleanup.

## Memory and performance

- Return an Eloquent `Builder`. Do not materialize the full result with `get()`, `all()`, or `toArray()` before generation.
- Select required columns and eager-load only relationships used by `item()` to avoid N+1 queries.
- `chunkSize()` defaults to `1000`. Smaller chunks trade lower hydration memory for more queries. Measure with realistic row widths and relationships.
- `perFile()` defaults to `0`, meaning no record-count split. Use it when consumers accept parts and individual files become hard to publish or retry.
- `maxFiles()` defaults to `0`, meaning no cap. Positive `perFile()` and `maxFiles()` intentionally cap exported rows at their product; later rows are omitted without a failure. Never add a cap to make a complete export faster.
- Queue mode moves work to a worker but does not change feed memory. Compare whole-path time, peak memory, row count, and storage conditions before claiming an improvement.

## Diagnose the first failing boundary

| Symptom | Check |
| --- | --- |
| Class not found | Registration FQCN, subclass contract, Composer autoload; run `composer dump-autoload` when a move requires it. |
| Unexpected inactive run | An explicit slug intentionally bypasses activity filtering. |
| Job never runs | Connection/name, matching worker, async versus sync mode, uniqueness lock, timeout and memory. Agent synchronous success does not validate asynchronous delivery. |
| XML failure | Raw fragment and CDATA validity; normal escaping where raw data is unnecessary. |
| CSV fails after first row | Stable output keys and scalar fields; optional values keep empty columns. |
| Rejected disk or failed write | Adapter type, write capability, credentials and permissions; distinguish preflight from publication. |
| Stale or incomplete remote output | Original exception, rollback state, consistency behavior, and cross-host locking. |
| Growing staging directories | Repeated publication/cleanup failures or interruption; inspect backups before cleanup. |

Serialization and stream causes include `InvalidCsvRowException`, `InvalidCsvValueException`, `InvalidXmlCDataException`, `InvalidXmlFragmentException`, `OpenFeedException`, `WriteFeedException`, `CloseFeedException`, and `ResourceMetaException`. Preserve the full cause chain instead of treating every failure as a queue issue.
