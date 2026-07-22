<?php

declare(strict_types=1);

namespace Tests\Helpers\Benchmark;

use AssertionError;
use RuntimeException;

final class BenchmarkRegression
{
    public function record(string $directory, string $format, float $averageTime): void
    {
        if ($averageTime <= 0) {
            throw new RuntimeException('Benchmark average time must be greater than zero.');
        }

        if (! is_dir($directory) && ! mkdir($directory, 0o777, true) && ! is_dir($directory)) {
            throw new RuntimeException("Unable to create benchmark results directory: [$directory].");
        }

        $path    = $directory . DIRECTORY_SEPARATOR . $format . '.json';
        $content = json_encode(['average_time_ms' => $averageTime], JSON_THROW_ON_ERROR) . PHP_EOL;

        if (file_put_contents($path, $content) === false) {
            throw new RuntimeException("Unable to write benchmark result: [$path].");
        }
    }

    public function assertWithinLimits(string $directory, array $limits, int $expectedRuns): void
    {
        if ($expectedRuns < 1) {
            throw new RuntimeException('Expected benchmark runs must be greater than zero.');
        }

        foreach ($limits as $format => $limit) {
            if ($limit < 0) {
                throw new RuntimeException("Benchmark regression limit for [$format] must not be negative.");
            }

            $baseline  = $this->median($this->times($directory, 'base', $format, $expectedRuns));
            $candidate = $this->median($this->times($directory, 'candidate', $format, $expectedRuns));
            $maximum   = $baseline * (1 + $limit / 100);

            if ($candidate <= $maximum) {
                continue;
            }

            $regression = round(($candidate / $baseline - 1) * 100, 2);

            throw new AssertionError(
                "The [$format] generation time regression must be less than or equal to $limit%. Current value: $regression%."
            );
        }
    }

    protected function times(
        string $directory,
        string $variant,
        string $format,
        int $expectedRuns,
    ): array {
        $pattern = implode(DIRECTORY_SEPARATOR, [$directory, $variant . '-*', $format . '.json']);
        $files   = glob($pattern) ?: [];

        sort($files, SORT_STRING);

        if (count($files) !== $expectedRuns) {
            throw new RuntimeException(
                "Expected $expectedRuns [$variant] benchmark results for [$format], found " . count($files) . '.'
            );
        }

        return array_map(function (string $path): float {
            $content = file_get_contents($path);

            if ($content === false) {
                throw new RuntimeException("Unable to read benchmark result: [$path].");
            }

            $time = json_decode($content, true, flags: JSON_THROW_ON_ERROR)['average_time_ms'] ?? null;

            if ((! is_int($time) && ! is_float($time)) || $time <= 0) {
                throw new RuntimeException("Invalid benchmark average time in: [$path].");
            }

            return (float) $time;
        }, $files);
    }

    protected function median(array $values): float
    {
        sort($values, SORT_NUMERIC);

        $count  = count($values);
        $middle = intdiv($count, 2);

        if ($count % 2 === 1) {
            return $values[$middle];
        }

        return ($values[$middle - 1] + $values[$middle]) / 2;
    }
}
