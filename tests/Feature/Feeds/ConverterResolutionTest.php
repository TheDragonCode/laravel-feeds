<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Converters\JsonConverter;
use DragonCode\LaravelFeed\Services\GeneratorService;
use Workbench\App\Feeds\SplitJsonFeed;
use Workbench\App\Models\News;

test('resolves one converter for each generation regardless of row count', function () {
    $resolutions = 0;

    app()->resolving(JsonConverter::class, function () use (&$resolutions) {
        $resolutions++;
    });

    News::factory()->count(1)->create();

    app(GeneratorService::class)->feed(app(SplitJsonFeed::class));

    News::factory()->count(20)->create();

    app(GeneratorService::class)->feed(app(SplitJsonFeed::class));

    expect($resolutions)->toBe(2);
});
