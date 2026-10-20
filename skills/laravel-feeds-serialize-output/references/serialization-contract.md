# Serialization contract

## Format and option selection

Set the typed `$format` property to a `FeedFormatEnum` case. XML is the default.

| Case / value | Contract | Per-feed option names |
| --- | --- | --- |
| `Xml` / `xml` | XML elements and item attributes | `pretty` |
| `Json` / `json` | One complete JSON document | `options`, `pretty` |
| `JsonLines` / `jsonl` | One encoded item per line; no root wrapper | `options` |
| `Csv` / `csv` | Flat rows; no XML/JSON root wrapper | `delimiter`, `enclosure`, `escape`, `lineEnding` |
| `Rss` / `rss` | XML-based RSS serialization; choose a preset for its envelope | `pretty` |

Import `DragonCode\LaravelFeed\Attributes\Config`, not Laravel's container
attribute of the same short name. Apply repeatable `#[Config('name', value)]`
attributes to feed classes. Names are constructor parameters: use `lineEnding`,
not `line_ending` or a dotted configuration key.

Only supplied values override defaults. Child keys override inherited keys;
the last repeated key on one class wins. Inherited CSV keys remain present if a
child switches format, so do not derive a JSON feed from a CSV-configured class.
Unknown options for a format throw `InvalidArgumentException`.

Application defaults include semicolon CSV delimiters, double-quote enclosures,
empty CSV escape, and `PHP_EOL`. JSON/JSONL default to unescaped slashes and
Unicode. JSONL strips `JSON_PRETTY_PRINT`; setting `pretty` on JSONL is invalid.

## Structure and omission

- `root(): ElementData` controls name, attributes, and `beforeInfo` (default
  `true`). `null` or an empty name suppresses the wrapper. Default root names
  derive from the feed class; item names derive from the model class.
- JSON normally places items under the root key; a suppressed root gives a bare
  array. Check metadata placement against the consumer's required JSON shape.
- `info(): FeedInfo` supplies metadata in every format. JSONL metadata is a
  separate line; CSV metadata is a separate row, not an automatic column header.
- `beforeInfo: false` moves metadata before the root. XML with metadata outside
  the root needs a surrounding envelope to remain a single valid document.
- Feed `header()` and `footer()` can supply XML/RSS envelope fragments. Balance
  their tags with the root. Inspect the selected converter before assuming
  custom header/footer hooks affect other formats.
- Item `name()` and `attributes()` control the XML item element. RSS converters
  may force the item name; preserve preset behavior.
- `OptionalData` means omission, not `null`. JSON/XML omit optional fields;
  CSV retains the cell as an empty value. Keep all CSV item keys identical.
- CSV rejects arrays and inconsistent item key sets. Later rows are reordered
  to match the first item's keys. Flatten nested data explicitly when needed.

## XML decisions

Use these patterns for XML; check an RSS preset's converter contract before
adapting them. Do not expect directive keys to have special meaning in JSON,
JSONL, or CSV.

| Pattern | Meaning | Boundary |
| --- | --- | --- |
| `@attributes` | Set attributes on the current node | Supply an attribute array |
| `@value` | Replace node content with text | Supply a string; do not combine with child content that must survive |
| `@cdata` | Preserve text inside CDATA | Supply a string; check embedded terminators and converter failures |
| `@mixed` | Append a parsed XML fragment | Supply valid trusted XML, not interpolated external input |
| `@tagName` | Repeat sibling elements named `tagName` | Supply a list; this is not a literal `@custom` directive |

Prefer normal array values for text so the converter escapes it. CDATA preserves
markup as text; it does not sanitize HTML. Invalid fragments raise
`InvalidXmlFragmentException`; invalid CDATA raises `InvalidXmlCDataException`.
Do not remove XML validation or suppress these failures to publish a broken file.

## Value pipeline and extension hooks

Implement `DragonCode\LaravelFeed\Contracts\Transformer` with
`allow(mixed $value): bool` and
`transform(mixed $value): string`.
Register class names, not instances. The service container resolves constructor
dependencies and custom bindings.

Order: feed `transformers()` first, configured transformers second, converter
transformers last. Built-in configured order is boolean, date, enum. Every
matching transformer runs on the current value, including earlier changes.
Duplicate resolved transformers are removed while preserving the first position.
Instances/pipelines are reused within generation; avoid mutable per-record state.

Date formatting defaults to `DATE_ATOM` and UTC. Return `DateTimeInterface`
objects rather than already serialized model date strings. An item `DateFormat`
attribute formats its dates before the normal pipeline and keeps the configured
timezone. It covers nested item values and item attributes, but not metadata or
root attributes. The nearest annotated item ancestor supplies an inherited format.

Use `when()`/`unless()` for conditional item construction only when it improves
clarity. `Feed`, `FeedInfo`, and `FeedItem` support both conditional helpers and
macros. Register shared macros in an application provider; prefer local methods
when the helper has only one caller.

If an existing custom `ConverterHelper` overrides `get()` or `resolve()`, it
must also override `resolveWithOptions(FeedFormatEnum $format, array $options = []): Converter`
to support per-feed settings. Pass options to container construction before the
converter initializes. Custom bindings must consume those parameters. Without
this hook, options cause `LogicException`; feeds without options retain legacy
resolution. Do not add a helper subclass merely to set a delimiter.
