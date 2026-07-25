<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Data\ElementData;
use DragonCode\LaravelFeed\Data\OptionalData;
use DragonCode\LaravelFeed\Feeds\Info\FeedInfo;
use DragonCode\LaravelFeed\Services\GeneratorService;
use Workbench\App\Data\NewsFakeData;
use Workbench\App\Feeds\JsonInfoFeed;
use Workbench\App\Feeds\JsonRootInfoFeed;
use Workbench\App\Models\News;

final class JsonRootBeforeInfoFeed extends JsonInfoFeed
{
    public function root(): ElementData
    {
        return new ElementData('items');
    }

    public function filename(): string
    {
        return 'json-root-before-info.json';
    }
}

final class JsonOptionalInfoFeed extends JsonRootInfoFeed
{
    public function info(): FeedInfo
    {
        return new class extends FeedInfo {
            public function toArray(): array
            {
                return [
                    'optional' => new OptionalData,
                ];
            }
        };
    }

    public function filename(): string
    {
        return 'json-optional-info.json';
    }
}

test('keeps info separators valid', function () {
    $cases = [
        'rootless empty feed'                   => [JsonInfoFeed::class, false, '0.name', 'Laravel', null],
        'root before info empty feed'           => [JsonRootBeforeInfoFeed::class, false, 'items.0.name', 'Laravel', null],
        'info before root empty feed'           => [JsonRootInfoFeed::class, false, 'name', 'Laravel', null],
        'root before info with items'           => [JsonRootBeforeInfoFeed::class, true, 'items.0.name', 'Laravel', 'items.1.title'],
        'fully optional info before root'       => [JsonOptionalInfoFeed::class, false, 'optional', null, null],
        'fully optional info before root items' => [JsonOptionalInfoFeed::class, true, 'optional', null, 'items.0.title'],
    ];

    foreach ([false, true] as $pretty) {
        setPrettyXml($pretty);

        foreach ($cases as $case => [$class, $withItems, $infoPath, $expected, $itemPath]) {
            News::query()->delete();

            if ($withItems) {
                createNews(...NewsFakeData::toArray());
            }

            $feed = app($class);

            app(GeneratorService::class)->feed($feed);

            try {
                $document = json_decode(
                    json       : readFeedFile($feed->path()),
                    associative: true,
                    flags      : JSON_THROW_ON_ERROR
                );
            } catch (JsonException $exception) {
                throw new RuntimeException(
                    "Invalid JSON for [$case] with pretty [" . ($pretty ? 'true' : 'false') . '].',
                    previous: $exception
                );
            }

            expect($document)
                ->toBeArray()
                ->and(data_get($document, $infoPath))
                ->toBe($expected);

            if ($itemPath !== null) {
                expect(data_get($document, $itemPath))->toBe('Some 1');
            }

            if ($class === JsonOptionalInfoFeed::class) {
                expect($document)
                    ->toHaveKey('items')
                    ->not->toHaveKey('optional');
            }
        }
    }
});
