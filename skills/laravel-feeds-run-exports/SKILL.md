---
name: laravel-feeds-run-exports
description: Run, schedule, and diagnose registered Laravel Feeds exports, interpret command results, and verify publication, queue delivery, and streaming performance.
argument-hint: "<registration slug or runtime problem>"
---

# Run registered exports

Operate existing feed registrations. Use this skill to list, generate, queue, schedule, or diagnose exports. Produce the exact command or runtime change, published-file evidence, and any unresolved delivery or performance limits.

## Workflow

1. Inspect the installed package, registration, feed class, `config/feeds.php`, and relevant queue/storage settings. Use `php artisan feed:list` for discovery; include inactive and deleted states when selecting a registration.
2. Match execution scope to the request. No slug runs active registrations and skips inactive ones; an explicit non-deleted registration slug runs even when inactive. Diagnose read-only until generation or changes are authorized.
3. Choose direct generation, normal asynchronous queue dispatch, or scheduler integration. Recognized agents receive automatic JSON and execute queue jobs synchronously. There is no `--agent` option.
4. Run the selected path. For programmatic use, resolve `GeneratorService` from the container. A targeted feed must be configured with `forTarget()` first; the service does not enumerate targets.
5. Verify the actual output: all result paths, per-file counts, storage publication, and consumer delivery when in scope. A human console `QUEUED` status proves dispatch, not worker completion. Inspect the original exception on failure.
6. For slow or memory-heavy runs, measure query, hydration, mapping, conversion, and publication separately. Keep the query lazy, then change one measured bottleneck and compare the same dataset.

Use [the runtime contract](references/RUNBOOK.md) for CLI schemas, queues, events, storage guarantees, and recovery. Use [the operational examples](examples/OPERATIONS.md) for complete integration patterns.

## Completion checks

- State the registration slug, target scope, mode, and whether inactive execution was intentional.
- Distinguish successful generation, job dispatch, and downstream delivery.
- Inspect every published part; do not infer all paths from the first finished-event path.
- Report measured performance with row count and storage context. Queue mode does not reduce feed memory.
- Preserve staging backups until failed rollback and publication state are understood.

## Artifact ownership and config policy

Read registrations, feed definitions, application config, scheduler code, logs, queue state, and output files as context. Write only requested application runtime/config/schedule changes. Authorized generation writes output files, registration `last_activity`, and lifecycle events; queue mode can also dispatch jobs. Persist registration changes through the application's migration or deployment-operation convention. Do not delete registrations, stale staging backups, or change unrelated settings during diagnosis. Treat `.env` values as secrets and report setting names without exposing credentials.
