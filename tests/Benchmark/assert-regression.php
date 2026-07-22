<?php

declare(strict_types=1);

use Tests\Helpers\Benchmark\BenchmarkRegression;
use Tests\Helpers\Benchmark\RegressionThresholds;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

if ($argc !== 3) {
    throw new RuntimeException('Expected benchmark results directory and run count.');
}

(new BenchmarkRegression)->assertWithinLimits(
    $argv[1],
    RegressionThresholds::limits(),
    (int) $argv[2],
);
