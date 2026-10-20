---
name: laravel-feeds-target-outputs
description: Implement independent Laravel Feeds outputs for partners, stores, locales, or other business keys with target-specific queries and collision-free filenames.
argument-hint: "<business key, query conditions, and output contract>"
---

# Implement business-specific output files

Build one registered feed with a separate filtered output for each business key. Use this skill when implementing target discovery, lookup, query filtering, and filenames. Produce a complete targeted feed, selection examples, and checks that target datasets and paths stay independent.

## Workflow

1. Identify the registration, business key, selection conditions, output contract, and expected target count. Choose targets for business variants, `perFile()` for count-based parts, or both when each variant needs parts.
2. Inspect the existing feed and callers. Keep one registration for the class. Implement `HasFeedTargets` with `InteractsWithFeedTargets`; keep `targets()` lazy and make `findTarget()` resolve explicit keys directly.
3. Design stable non-empty keys and small serializable parameter snapshots. Keys are visible in output and errors and are not sanitized. Use controlled path-safe keys, identifiers instead of models, and no secrets.
4. Use `target()->parameters` to filter `builder()` and a deterministic distinct `filename()` for every target. Return a lazy Eloquent builder. Resolve only required relationships and fields.
5. Exercise full enumeration, selected-key lookup, unknown keys, clone independence, dataset filtering, and filename uniqueness. For parts, verify suffixes and total counts independently per target.
6. Show the registration slug and repeatable `--target` commands. For direct service calls, resolve a target and pass `forTarget($target)`; `GeneratorService` never enumerates targets.

Use [the target contract](references/TARGET-CONTRACT.md) for execution and failure rules. Use [the complete locale example](examples/LOCALE-FEED.md) for code, invocation, and a small assertion check.

## Artifact ownership and config policy

Read current feed definitions, model schema, registrations, application storage/config, and call sites. Write only requested target implementation and relevant application tests or reproducible registration changes. Do not create one registration per target. Generation and queue dispatch change application state; perform them only within the requested scope. Retain the existing format, disk, and unrelated query rules unless the output contract requires a change. Do not expose secrets in keys, snapshots, logs, or filenames. This skill does not write AI Factory context or config.
