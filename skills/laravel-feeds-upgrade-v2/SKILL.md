---
name: laravel-feeds-upgrade-v2
description: Upgrade applications from Laravel Feeds 1.x to 2.x while preserving registrations, output contracts, and rollback data. Use for runtime compatibility, slug migration, ID-based generation calls, and changed generated registration titles.
argument-hint: "<application> [current version and deployment constraints]"
---

# Upgrade to version 2

Prepare and implement a safe application upgrade from 1.x to 2.x. Produce a scoped compatibility assessment, migration and caller changes, verification evidence, and an explicit rollback sequence.

## Workflow

1. Read installed and intended package versions, `composer.json`, `composer.lock`, configuration, migration history, feed registrations, generation callers, and deployment processes. Follow [upgrade rules](references/upgrade-rules.md); verify version constraints for the actual target release.
2. Check PHP and Laravel compatibility across web, CLI, scheduler, and queue workers. If the application cannot meet the target constraints, stop the package upgrade and report the incompatible environment.
3. Preserve code, dependency files, published configuration, registration data, and representative output for rollback. Plan to pause schedules and drain feed jobs before deployment changes.
4. Update dependencies; merge configuration changes. Inspect whether slug support already exists. Add only the needed migration, after the actual registration-table creation and before registration migrations; resolve schema conflicts without discarding application data.
5. Validate non-empty, distinct slugs across all non-deleted registrations. Replace registration IDs in command and manual-schedule arguments with actual stored slugs. Keep target keys and ID-based query methods unchanged.
6. Check newly generated titles separately from existing registrations. Preserve completed changes from later 1.x releases rather than applying them twice.
7. Verify a clean migration replay on an isolated database, upgraded existing data, representative formats, split output, targets, custom extensions, queues, and schedules as used. Refresh caches and restart long-running processes before resuming generation.
8. Report changed artifacts, checks, unresolved constraints, deployment sequence, and rollback readiness. Use [migration examples](examples/upgrade-patterns.md) for concrete commands and caller updates.

## Artifact ownership and config policy

Own only upgrade-related dependency files, published feed configuration, required migrations, generation callers, deployment instructions, and focused application tests. Read the actual configured registration model/table/connection; do not assume a default table. Do not force-republish configuration, duplicate table-creation migrations, alter unrelated data, or run destructive verification against a shared database. Prepare operational steps without executing a production migration or deleting output unless the task authorizes that action. Treat unrelated application and agent artifacts as read-only.
