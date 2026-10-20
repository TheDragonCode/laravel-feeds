---
name: laravel-feeds-build-presets
description: Build Sitemap, Instagram catalog, Yandex catalog, and RSS feeds with Laravel Feeds presets. Use when a feed must match one of these service contracts, including metadata, categories, namespaces, and registration.
argument-hint: "<preset> <data source> <required fields and destination>"
---

# Build preset feeds

Create a registered preset subclass with the right query, item mapping, metadata, destination, and output checks. Use this for a named preset or service integration; produce implementation changes and the measured generation result.

## Workflow

1. Inspect the installed package version, existing feed definitions, registration mechanism, models, relationships, and destination disk. Verify APIs against that installed version before editing.
2. Choose the preset in [preset contracts](references/preset-contracts.md). Confirm the required fields and receiver contract. An RSS preset with an Atom namespace still emits RSS 2.0.
3. Reuse an existing definition or run `php artisan make:feed <name>`. Extend the chosen preset. Implement `builder()` and `item(Model $model)`; call `parent::item()` to keep its item type and defaults. Add `info()` only for needed metadata.
4. Filter or reject invalid source data deliberately. Eager-load relationships used per item. Map scalar category names or IDs rather than passing relationship objects to string setters.
5. Keep preset headers, namespaces, root order, and footers unless the receiver requires a supported change. Validate raw XML directive arrays before passing them through `additional()` or `rawCategory()`.
6. Review the generated registration migration or deploy operation, apply the mechanism the application uses, and inspect the stored slug. Generate that slug and check output paths, XML structure, required fields, counts, and split files.
7. Report the preset, changed artifacts, registration slug, generated files, checks performed, and receiver checks still pending. Successful XML generation alone does not establish acceptance by an external service.

Use [navigation and news examples](examples/navigation-news.md) for Sitemap and RSS, or [catalog examples](examples/catalogs.md) for Instagram and Yandex.

## Artifact ownership and config policy

Own only requested feed definitions, supporting item/info classes, registration migrations or operations, and their focused tests. Read `config/feeds.php`, `config/filesystems.php`, and model contracts first; change them only when the requested destination or generation behavior requires it. Merge settings rather than replacing application configuration. Write generated output only to the chosen disk. Publish public symlinks or edit a root sitemap only when public discovery is part of the task. Treat unrelated application and agent artifacts as read-only.
