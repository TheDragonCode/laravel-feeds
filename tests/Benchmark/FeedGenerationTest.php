<?php

declare(strict_types=1);

use DragonCode\Benchmark\Benchmark;
use DragonCode\LaravelFeed\Enums\FeedFormatEnum;
use Illuminate\Support\Facades\Storage;
use Tests\Helpers\Benchmark\RegressionFeed;
use Tests\Helpers\Benchmark\RegressionGeneratorService;

beforeEach(function () {
    config()->set('feeds.pretty', false);

    $this->application = app();
    $this->generator   = app(RegressionGeneratorService::class);
    $this->models      = makeRegressionFeedModels(2000);
    $this->storage     = Storage::fake('public');
});

afterEach(function () {
    $this->storage->deleteDirectory('benchmark');
});

it('keeps feed generation time within the calibrated regression limit', function (
    FeedFormatEnum $format,
    float $max,
) {
    $path = 'benchmark/feed.' . $format->value;

    (new Benchmark)
        ->snapshots(dirname(__DIR__, 2) . '/.benchmarks')
        ->warmup(3)
        ->iterations(20)
        ->disableProgressBar()
        ->beforeEach(fn () => new RegressionFeed(
            $this->application,
            mockRegressionFeedBuilder($this->models),
            $format,
        ))
        ->afterEach(fn () => $this->storage->delete($path))
        ->compare([
            $format->value => fn (int $_iteration, RegressionFeed $feed) => $this->generator->feed($feed),
        ])
        ->toAssert()
        ->toBeRegressionTime(max: $max);

    expect($this->storage->exists($path))->toBeFalse();
})->with('feed generation regression formats');
