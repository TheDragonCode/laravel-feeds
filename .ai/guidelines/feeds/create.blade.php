## Creating Feeds

This guide uses the `JSON Lines` feed as an example.
When creating a new feed, follow these steps by analogy. Do not modify the actual Json Lines feed.

### Core functionality

1. Decide on the feed type and format (for example, `JSON Lines`).
2. Add the format to `Enums\FeedFormatEnum`:
   - The enum key must be in PascalCase (e.g., `JsonLines`).
   - The enum value must be the feed format in camelCase (e.g., `jsonl`).
3. Create a converter in `src/Converters` using the `stubs/converter.stub` template. In that stub:
   - Replace `DummyNamespace` with `DragonCode\LaravelFeed\Converters`.
   - Replace `DummyClass` with the feed class name, e.g., `JsonLinesConverter`. The `Converter` suffix is required.
   - The file name must match the class name, e.g., `JsonLinesConverter.php`.
4. Register your converter in `\DragonCode\LaravelFeed\Helpers\ConverterHelper::get` by analogy with the existing entries.

### Tests

1. Create example feeds under `workbench/app/Feeds`, mirroring the Json Lines example.
2. Reference the newly created feed classes in `workbench/database/seeders/FeedSeeder.php`.
3. Create four tests — `DefaultTest`, `InfoTest`, `RootInfoTest`, and `RootTest` — inside `tests/Feature/Feeds/Formats/*`, following the JsonLines tests as a template.
4. When calling `expectFeedSnapshot` in your tests, pass the reference to your feed class and the corresponding format from `FeedFormatEnum`.
5. Add a new expectation extension in `tests/Expectations.php` to validate the feed.
6. Update the `expectFeedSnapshot` helper in `tests/Helpers/expects.php` to handle your new type by calling the extension method you created.
7. Run the tests with `php vendor/bin/pest --filter JsonLines`, replacing `JsonLines` with the name of the folder where your tests live.
8. Fix any failing tests.

### Adding the feed to the documentation

1. Add a new label in `docs/labels.list`. The key used in the `ref` parameter must follow the `format-*` pattern, where `*` is the feed format in camelCase (e.g., `jsonl`).
2. Include this label in `docs/topics/supported-formats.topic`.
3. Add the label to the `information` chapter in `docs/topics/elements.topic`.
