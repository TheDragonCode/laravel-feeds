<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Enums\FeedFormatEnum;
use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Services\GeneratorService;
use Illuminate\Console\OutputStyle;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Workbench\App\Models\News;

final class QuerySemanticsFeed extends Feed
{
    public static Builder $query;

    protected FeedFormatEnum $format = FeedFormatEnum::JsonLines;

    public function builder(): Builder
    {
        return clone self::$query;
    }

    public function chunkSize(): int
    {
        return 2;
    }

    public function filename(): string
    {
        return 'query-semantics.jsonl';
    }
}

function querySemanticsIds(Builder $query, ?OutputStyle $output = null): array
{
    QuerySemanticsFeed::$query = $query;

    $feed = app(QuerySemanticsFeed::class);

    app(GeneratorService::class)->feed($feed, $output);

    return array_column(
        parseJsonLines(readFeedFile($feed->path())),
        'id'
    );
}

beforeEach(function () {
    createNews(
        ['title' => 'Delta', 'content' => 'one', 'category' => 'news'],
        ['title' => 'Alpha', 'content' => 'two', 'category' => 'news'],
        ['title' => 'Charlie', 'content' => 'three', 'category' => 'news'],
        ['title' => 'Bravo', 'content' => 'four', 'category' => 'news'],
    );
});

test('preserves non-primary-key ordering across chunks', function () {
    expect(querySemanticsIds(News::query()->orderBy('title')))
        ->toBe([2, 4, 3, 1]);
});

test('respects a query limit', function () {
    expect(querySemanticsIds(News::query()->orderBy('id')->limit(2)))
        ->toBe([1, 2]);
});

test('respects a query offset', function () {
    expect(querySemanticsIds(News::query()->orderBy('id')->offset(1)->limit(3)))
        ->toBe([2, 3, 4]);
});

test('counts bounded queries exactly for progress reporting', function () {
    $buffer = new BufferedOutput(OutputInterface::VERBOSITY_DEBUG);
    $output = new OutputStyle(new ArrayInput([]), $buffer);

    expect(querySemanticsIds(News::query()->orderBy('id')->offset(1), $output))
        ->toBe([2, 3, 4])
        ->and($buffer->fetch())
        ->toContain('3/3')
        ->not->toContain('[FIX:190]');
});

test('keeps unbounded queries streamed in configured chunks', function () {
    $batches = [];

    $query = News::query()
        ->orderBy('id')
        ->afterQuery(function ($models) use (&$batches) {
            $batches[] = $models->count();
        });

    expect(querySemanticsIds($query))
        ->toBe([1, 2, 3, 4])
        ->and($batches)
        ->not->toBeEmpty()
        ->and(max($batches))
        ->toBeLessThan(4);
});
