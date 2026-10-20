---
name: laravel-feeds-create-feed
description: Create and register Laravel Feeds exports with lazy Eloquent queries, explicit item fields, item date formats, and application-specific registration models. Use when adding a first feed or changing its data source and registration schema.
argument-hint: "<feed name and consumer schema>"
---

# Create a Feed

Build a registered export from an application's Eloquent models. Use this for installation, scaffolding, query selection, item mapping, or registration-model changes. Produce the feed and item classes, required registration/schema changes, and a verification result.

## Workflow

1. Inspect the installed package version, Composer constraints, existing feeds, registration model, database connection, and storage disk. Establish the consumer's format, fields, filters, date requirements, and permitted data exposure.
2. For a new installation, install and publish the package. Check `feeds.table.connection` and `feeds.table.table` before migrating. For an existing installation, inspect applied migrations and upgrade only the missing schema; do not republish a second create-table migration.
3. Scaffold with `php artisan make:feed Product --item`. Add `--info` only for required feed-level metadata. Inspect and apply the generated registration migration or deploy operation before generation. Avoid duplicate manual registration.
4. Implement `builder(): Builder` with filters, selected columns, and only the relationships the item reads. Keep the primary key and required relation keys selected. Return the builder directly; do not call `get()` or collect all records.
5. Implement `item(Model $model): FeedItem` and an explicit `toArray()` schema. Return raw date-cast values for date formatting. Use `OptionalData` for absent fields when omission is required. Do not rely on the base item exporting the entire model array.
6. Choose the feed's format and disk explicitly when the XML and `public` defaults do not fit. Use an item `DateFormat` attribute for item-only dates. Recognize the package's `Config` attribute for per-feed converter options; consult the serialization workflow for its format-specific parameters.
7. Keep registration titles distinct after slug normalization. Use model saves or `FeedQuery::create()`. Extend the package registration model only when additional application attributes are required; add their schema, fillable entries, and casts together.
8. Validate syntax, registration, representative item output, and the query on more than one chunk. Check row eligibility, required fields, absent values, dates, relation query counts, and destination visibility. Measure memory/query costs before changing the default chunk size of `1000`.

Read [the feed contract](references/feed-contract.md) for defaults and failure checks. Adapt [the complete patterns and checks](examples/create-feed.md) to the application's schema.

## Output

Report the changed classes and registration/schema artifacts, output format and disk, eligible records, checks run, and any environment-dependent checks left open. Claim generation success only after verifying the published artifact.

## Artifact Ownership and Config Policy

Write only the requested application's feed/item/info classes, registration migration or deploy operation, custom registration model, required schema, and focused tests. Edit existing configuration keys only when needed for this feed; preserve unrelated settings. Read the package configuration and Composer lock as context. Do not change other feeds, production data, application-wide date/converter settings, or deployment schedules without task authorization.
