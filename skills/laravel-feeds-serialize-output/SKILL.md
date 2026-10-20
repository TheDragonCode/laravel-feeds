---
name: laravel-feeds-serialize-output
description: >-
  Shape Laravel Feeds output for a consumer schema. Use when changing XML,
  JSON, JSON Lines, CSV, or RSS converter settings, root and metadata structure,
  XML directives, optional fields, or value transformers on an existing feed.
argument-hint: "<feed-or-item> <required-format-or-schema>"
---

# Shape feed output

Turn an existing feed into the required serialized contract. Produce the smallest
feed, item, info, or transformer change, an adapted example, and focused checks
of the generated content.

## Workflow

1. Inspect the installed package version, feed, item, info, converter bindings,
   and consumer schema. Identify required fields, types, escaping, date formats,
   encoding, and empty-output behavior before changing code.
2. Read [the serialization contract](references/serialization-contract.md).
   Select the format with `FeedFormatEnum`; use package `Config` attributes for
   per-feed settings. Keep application defaults when no override is needed.
3. Map fields explicitly. Use `ElementData`, `FeedInfo`, and item hooks for
   structure; use XML directives only where the selected converter supports them.
   Never insert untrusted strings as raw XML.
4. Return raw date objects for date formatting. Prefer item `DateFormat` for an
   item-only format; use `transformers()` for feed-specific value conversion.
   Check every subsequent matching transformer, not only the first match.
5. Adapt [formats and structure](examples/formats-and-structure.md),
   [XML patterns](examples/xml-patterns.md), or
   [extension patterns](examples/extension-patterns.md) to the application.
   Use a direct method before introducing a global macro or converter override.
6. Check an empty feed, two different records, missing optional values, special
   characters, and the required date/timezone behavior. Parse complete XML/JSON,
   decode each JSONL line, or read CSV with the configured dialect. Check the
   consumer's schema separately from syntactic validity.

## Completion checks

- Report the exact schema/settings changed and the checks actually run.
- Confirm stable CSV columns and scalar values; a blank optional cell retains its column.
- Confirm unrelated feeds keep their converter options and transformer behavior.
- Keep query construction, target routing, registration, and scheduling changes
  outside this task unless they are necessary for the requested output.

## Artifact ownership and config policy

Read the consumer contract and relevant application feed classes, models, tests,
and configuration. Edit only the requested feed/item/info/transformer classes
and focused tests. Change `config/feeds.php` or provider bindings only when an
application-wide change is requested. Preserve unrelated keys. Do not write
generated exports into a live publication location for a diagnostic check.
This skill does not use or modify AI Factory configuration or planning artifacts.
