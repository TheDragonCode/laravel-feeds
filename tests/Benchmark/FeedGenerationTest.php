<?php

declare(strict_types=1);

use DragonCode\Benchmark\Benchmark;
use DragonCode\LaravelFeed\Data\GenerationResultData;
use DragonCode\LaravelFeed\Enums\FeedFormatEnum;
use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Services\GeneratorService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\LazyCollection;

final class RegressionFeedModel extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function toArray(): array
    {
        return $this->getAttributes();
    }
}

final class RegressionFeed extends Feed
{
    public function __construct(
        Application $laravel,
        protected Builder $query,
        FeedFormatEnum $format,
    ) {
        parent::__construct($laravel);

        $this->format   = $format;
        $this->filename = 'benchmark/feed.' . $format->value;
    }

    public function builder(): Builder
    {
        return $this->query;
    }
}

final class RegressionGeneratorService extends GeneratorService
{
    protected function setLastActivity(Feed $feed): void {}

    protected function started(Feed $feed): void {}

    protected function finished(Feed $feed, GenerationResultData $result): void {}
}

function regressionFeedModels(int $count): array
{
    $createdAt = new DateTimeImmutable('2026-01-01T00:00:00+00:00');
    $models    = [];

    for ($id = 1; $id <= $count; $id++) {
        $model = new RegressionFeedModel;
        $model->setRawAttributes([
            'id'          => $id,
            'sku'         => 'SKU-' . $id,
            'title'       => 'Benchmark product ' . $id,
            'description' => 'Representative export field set ' . $id,
            'price'       => ($id % 10000) / 100,
            'active'      => $id % 2 === 0,
            'created_at'  => $createdAt,
            'updated_at'  => $createdAt,
            'category'    => 'category-' . ($id % 25),
            'stock'       => $id % 500,
        ]);

        $models[] = $model;
    }

    return $models;
}

function regressionFeedBuilder(array $models): Builder
{
    $builder = Mockery::mock(Builder::class);

    $builder->shouldNotReceive('count');
    $builder
        ->shouldReceive('lazyById')
        ->once()
        ->with(1000)
        ->andReturn(LazyCollection::make($models));

    return $builder;
}

function assertFeedGenerationRegression(FeedFormatEnum $format, float $max): void
{
    config()->set('feeds.pretty', false);

    $application = app();
    $generator   = app(RegressionGeneratorService::class);
    $models      = regressionFeedModels(2000);
    $path        = 'benchmark/feed.' . $format->value;
    $storage     = Storage::fake('public');

    try {
        (new Benchmark)
            ->snapshots(dirname(__DIR__, 2) . '/.benchmarks')
            ->warmup(3)
            ->iterations(20)
            ->disableProgressBar()
            ->beforeEach(static fn () => new RegressionFeed(
                $application,
                regressionFeedBuilder($models),
                $format,
            ))
            ->afterEach(static fn () => $storage->delete($path))
            ->compare([
                $format->value => static fn (int $_iteration, RegressionFeed $feed) => $generator->feed($feed),
            ])
            ->toAssert()
            ->toBeRegressionTime(max: $max);
    } finally {
        $storage->deleteDirectory('benchmark');
    }

    expect($storage->exists($path))->toBeFalse();
}

dataset('feed generation regression formats', [
    'xml'        => [FeedFormatEnum::Xml, 20.0],
    'json'       => [FeedFormatEnum::Json, 30.0],
    'json lines' => [FeedFormatEnum::JsonLines, 20.0],
    'csv'        => [FeedFormatEnum::Csv, 20.0],
    'rss'        => [FeedFormatEnum::Rss, 20.0],
]);

it('keeps feed generation time within the calibrated regression limit', function (
    FeedFormatEnum $format,
    float $max,
) {
    assertFeedGenerationRegression($format, $max);
})->with('feed generation regression formats');
