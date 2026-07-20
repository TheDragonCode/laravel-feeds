# Implementation Plan: Failure-Safe Feed Publication

Branch: fix/failure-safe-feed-publication
Created: 2026-07-20

## Original Request

implement [https://github.com/TheDragonCode/laravel-feeds/issues/161](https://github.com/TheDragonCode/laravel-feeds/issues/161)

## Settings

- Testing: yes
- Logging: verbose
- Docs: no

## Tasks

### Phase 1: Publication Transaction

- [x] Task 1: Refactor `src/Services/FilesystemService.php` to create collision-resistant drafts inside a generation-wide staging directory, acquire a deterministic per-feed exclusive lock, back up all current outputs before commit, publish the staged target set, restore every prior output after a failed mutation, remove obsolete numeric split parts only after all new parts are ready, and clean staging drafts/backups in `finally`. Preserve `release()` compatibility. Logging: expose no Laravel facade logging; failures must retain path and operation context through exceptions, while verbose lifecycle logs remain controllable by the caller.

### Phase 2: Generation Lifecycle

- [x] Task 2: Update `src/Services/GeneratorService.php` and `src/Services/ExportService.php` so every split part is fully converted and closed into staging before one publication commit, an open draft is always closed after conversion failure, and last-activity/events occur only around a successful generation. Add DEBUG-only console lifecycle output for lock/staging, staged part count, commit completion, and failure context; never use `Illuminate\\Support\\Facades\\Log`. Depends on Task 1.

### Phase 3: Regression Coverage

- [x] Task 3: Extend `tests/Unit/Services/FilesystemServiceTest.php` and `tests/Unit/Services/ExportServiceTest.php` with coverage for unique draft paths, exclusive per-feed locks, move failure rollback, obsolete-output handling, and resource cleanup after export failure. Logging: assertions must cover contextual exceptions or DEBUG output where applicable, with no unconditional test output. Depends on Tasks 1 and 2.

- [x] Task 4: Add end-to-end feed publication coverage under `tests/Feature/Feeds/` proving that a later split conversion failure preserves all old parts, regenerating three parts as one removes exactly the two obsolete parts, unrelated files remain untouched, and staging drafts/backups disappear after success and failure. Logging: exercise normal silent output and keep DEBUG diagnostics opt-in. Depends on Tasks 1, 2, and 3.
