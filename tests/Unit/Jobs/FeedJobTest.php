<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Data\GenerationResultData;
use DragonCode\LaravelFeed\Jobs\FeedJob;
use DragonCode\LaravelFeed\Services\GeneratorService;
use Workbench\App\Feeds\FullFeed;
use Workbench\App\Feeds\SitemapFeed;

test('configuration', function () {
    config()->set([
        'feeds.queue.connection' => 'redis',
        'feeds.queue.name'       => 'feeds',
        'feeds.queue.unique_ttl' => 120,
    ]);

    $job = new FeedJob(FullFeed::class);

    expect($job->connection)->toBe('redis')
        ->and($job->queue)->toBe('feeds')
        ->and($job->uniqueFor())->toBe(120);
});

test('serialization', function () {
    $job = unserialize(serialize(new FeedJob(FullFeed::class)));

    expect($job)->toBeInstanceOf(FeedJob::class)
        ->and($job->feedClass)->toBe(FullFeed::class);
});

test('generation', function () {
    $feed = app(FullFeed::class);

    app()->instance(FullFeed::class, $feed);

    $generator = mock(GeneratorService::class);
    $generator->shouldReceive('feed')
        ->once()
        ->with($feed)
        ->andReturn(new GenerationResultData([], []));

    (new FeedJob(FullFeed::class))->handle($generator);
});

test('unique id', function () {
    $first  = new FeedJob(FullFeed::class);
    $second = new FeedJob(SitemapFeed::class);

    expect($first->uniqueId())->toBe(FullFeed::class)
        ->and($second->uniqueId())->toBe(SitemapFeed::class)
        ->and($first->uniqueId())->not->toBe($second->uniqueId());
});
