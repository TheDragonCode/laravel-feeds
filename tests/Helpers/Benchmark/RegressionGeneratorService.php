<?php

declare(strict_types=1);

namespace Tests\Helpers\Benchmark;

use DragonCode\LaravelFeed\Data\GenerationResultData;
use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Services\GeneratorService;

final class RegressionGeneratorService extends GeneratorService
{
    protected function setLastActivity(Feed $feed): void {}

    protected function started(Feed $feed): void {}

    protected function finished(Feed $feed, GenerationResultData $result): void {}
}
