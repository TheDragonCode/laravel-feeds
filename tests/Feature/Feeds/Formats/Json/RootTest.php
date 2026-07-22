<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Data\ElementData;
use DragonCode\LaravelFeed\Enums\FeedFormatEnum;
use DragonCode\LaravelFeed\Services\GeneratorService;
use Workbench\App\Data\NewsFakeData;
use Workbench\App\Feeds\JsonRootFeed;

final class EscapedJsonRootFeed extends JsonRootFeed
{
    public const ROOT = "quote\" backslash\\ newline\nUnicode ключ";

    public function root(): ElementData
    {
        return new ElementData(
            name      : self::ROOT,
            beforeInfo: false
        );
    }

    public function filename(): string
    {
        return 'escaped-json-root.json';
    }
}

final class NumericJsonRootFeed extends JsonRootFeed
{
    public const ROOT = '123';

    public function root(): ElementData
    {
        return new ElementData(
            name      : self::ROOT,
            beforeInfo: false
        );
    }

    public function filename(): string
    {
        return 'numeric-json-root.json';
    }
}

test('export', function (bool $pretty) {
    setPrettyXml($pretty);

    createNews(...NewsFakeData::toArray());

    expectFeedSnapshot(JsonRootFeed::class, FeedFormatEnum::Json);
})->with('boolean');

test('serializes root names as valid JSON object keys', function () {
    $cases = [
        'escaped root' => [
            EscapedJsonRootFeed::class,
            EscapedJsonRootFeed::ROOT,
            JSON_HEX_QUOT | JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            ['\\u0022', 'ключ'],
        ],
        'numeric root with numeric check' => [
            NumericJsonRootFeed::class,
            NumericJsonRootFeed::ROOT,
            JSON_NUMERIC_CHECK | JSON_THROW_ON_ERROR,
            ['"123"'],
        ],
    ];

    createNews(...NewsFakeData::toArray());

    foreach ([false, true] as $pretty) {
        setPrettyXml($pretty);

        foreach ($cases as [$class, $root, $options, $encodedFragments]) {
            config()?->set('feeds.converters.json.options', $options);

            $feed = app($class);

            app(GeneratorService::class)->feed($feed);

            $content  = readFeedFile($feed->path());
            $document = json_decode(
                json       : $content,
                associative: true,
                flags      : JSON_THROW_ON_ERROR
            );

            expect($document)
                ->toBeArray()
                ->and(array_key_exists($root, $document))
                ->toBeTrue()
                ->and($document[$root])
                ->toHaveCount(count(NewsFakeData::toArray()));

            foreach ($encodedFragments as $fragment) {
                expect($content)->toContain($fragment);
            }
        }
    }
});
