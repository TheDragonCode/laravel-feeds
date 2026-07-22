<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Enums\FeedFormatEnum;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use Tests\Helpers\Benchmark\BenchmarkRegression;

test('compares median benchmark times without failing on isolated noise', function () {
    $directory  = (new TemporaryDirectory)->create();
    $regression = new BenchmarkRegression;

    try {
        foreach ([100.0, 100.0, 100.0, 500.0] as $index => $time) {
            $regression->record($directory->path('base-' . ($index + 1)), 'jsonl', $time);
        }

        foreach ([119.0, 120.0, 120.0, 500.0] as $index => $time) {
            $regression->record($directory->path('candidate-' . ($index + 1)), 'jsonl', $time);
        }

        $regression->assertWithinLimits($directory->path(), ['jsonl' => 20.0], 4);

        expect(true)->toBeTrue();
    } finally {
        $directory->delete();
    }
});

it('rejects an intentional slowdown beyond the format limit', function (
    FeedFormatEnum $format,
    float $limit,
) {
    $directory  = (new TemporaryDirectory)->create();
    $regression = new BenchmarkRegression;
    $candidate  = 100 * (1 + ($limit + 1) / 100);

    try {
        foreach (range(1, 4) as $run) {
            $regression->record($directory->path("base-$run"), $format->value, 100.0);
            $regression->record($directory->path("candidate-$run"), $format->value, $candidate);
        }

        expect(fn () => $regression->assertWithinLimits(
            $directory->path(),
            [$format->value => $limit],
            4,
        ))->toThrow(AssertionError::class, "The [$format->value] generation time regression");
    } finally {
        $directory->delete();
    }
})->with('feed generation regression formats');
