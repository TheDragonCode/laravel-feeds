<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Data\ElementData;
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

test('keeps info separators valid', function () {
    $cases = [
        'rootless empty feed'         => [JsonInfoFeed::class, false, '0.name'],
        'root before info empty feed' => [JsonRootBeforeInfoFeed::class, false, 'items.0.name'],
        'info before root empty feed' => [JsonRootInfoFeed::class, false, 'name'],
        'root before info with items' => [JsonRootBeforeInfoFeed::class, true, 'items.0.name'],
    ];

    foreach ([false, true] as $pretty) {
        setPrettyXml($pretty);

        foreach ($cases as $case => [$class, $withItems, $infoPath]) {
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
                ->toBe('Laravel');

            if ($withItems) {
                expect(data_get($document, 'items.1.title'))->toBe('Some 1');
            }
        }
    }
});
