<?php

declare(strict_types=1);

use DragonCode\Benchmark\Benchmark;
use DragonCode\LaravelFeed\Enums\FeedFormatEnum;
use Illuminate\Support\Facades\Storage;
use Tests\Helpers\Benchmark\RegressionFeed;
use Tests\Helpers\Benchmark\RegressionGeneratorService;

beforeEach(fn () => config()->set('feeds.pretty', false));

afterEach(fn () => Storage::disk('public')->deleteDirectory('benchmark'));

it('keeps feed generation time within the calibrated regression limit', function (
    FeedFormatEnum $format,
    float $max,
) {
    $application = app();
    $generator   = app(RegressionGeneratorService::class);
    $models      = makeRegressionFeedModels(2000);
    $storage     = Storage::fake('public');
    $path        = 'benchmark/feed.' . $format->value;
    $feed        = fn () => new RegressionFeed(
        $application,
        mockRegressionFeedBuilder($models),
        $format,
    );

    $result = $generator->feed($feed());

    expect(array_sum($result->records))->toBe(2000);

    $storage->delete($path);

    (new Benchmark)
        ->snapshots(dirname(__DIR__, 2) . '/.benchmarks')
        ->warmup(3)
        ->iterations(20)
        ->disableProgressBar()
        ->beforeEach($feed)
        ->afterEach(fn () => $storage->delete($path))
        ->compare([
            $format->value => fn (int $_iteration, RegressionFeed $feed) => $generator->feed($feed),
        ])
        ->toAssert()
        ->toBeRegressionTime(max: $max);

    expect($storage->exists($path))->toBeFalse();
})->with('feed generation regression formats');
