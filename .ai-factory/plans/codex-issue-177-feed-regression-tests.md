# Implementation Plan: Feed Generation Performance Regression Tests

Branch: codex/issue-177-feed-regression-tests
Created: 2026-07-21

## Original Request

implement [https://github.com/TheDragonCode/laravel-feeds/issues/177](https://github.com/TheDragonCode/laravel-feeds/issues/177)

## Settings

- Testing: yes
- Logging: verbose
- Docs: no

## Tasks

### Phase 1: Benchmark Harness

- [x] Task 1: Add `dragon-code/benchmark` to `require-dev` in `composer.json`, add an isolated Composer script for `tests/Benchmark`, and keep performance tests out of the existing PHP/Laravel matrix. Benchmark progress must remain disabled; failures must be reported through assertions without Laravel facade logging.

### Phase 2: Regression Coverage

- [x] Task 2: Add deterministic feed-generation time regression coverage under `tests/Benchmark/` for every `FeedFormatEnum` case. Prepare models, builders, feeds, services, and output cleanup outside the measured callback; use warm-up runs and at least ten measured iterations; call only `GeneratorService::feed()` inside the timed callback; never add memory assertions. Depends on Task 1.

### Phase 3: Baselines and CI

- [x] Task 3: Run repeated benchmark passes in the pinned runtime, calibrate an explicit `toBeRegressionTime(max: ...)` threshold for each format, and commit all generated `.snap` baselines. Calibration output must remain opt-in and normal benchmark runs must stay quiet. Depends on Task 2.

- [x] Task 4: Add a dedicated GitHub Actions benchmark job using a pinned OS, PHP version, Composer version, no coverage, and a guard that fails when regression snapshots are created or moved during CI. Verify the isolated benchmark, regular tests, formatting, and repository diff. Depends on Task 3.

### Phase 4: Review Refinements

- [x] Task 5: Remove Docker from the benchmark GitHub Actions job and configure PHP, extensions, Composer, and coverage mode through the existing `setup-php` action.

- [x] Task 6: Move benchmark-only feed, model, generator, and factory helpers into focused files under `tests/Helpers/Benchmark/`.

- [x] Task 7: Move the feed-format regression dataset into `tests/Datasets/` using the project Pest dataset convention.

- [x] Task 8: Rewrite `FeedGenerationTest.php` as an idiomatic Pest test using Pest lifecycle hooks, relocate snapshots to the new assertion call site, and rerun validation.

- [x] Task 9: Restore the original `composer.lock` ignore rule and remove the generated lock file from the pull request.
