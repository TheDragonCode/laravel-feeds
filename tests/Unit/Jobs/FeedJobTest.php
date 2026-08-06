<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Data\GenerationResultData;
use DragonCode\LaravelFeed\Feeds\FeedTarget;
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
        ->and($job->target)->toBeNull()
        ->and($job->uniqueFor())->toBe(120);
});

test('serialization without target', function () {
    $job = unserialize(serialize(new FeedJob(FullFeed::class)));

    expect($job)->toBeInstanceOf(FeedJob::class)
        ->and($job->feedClass)->toBe(FullFeed::class)
        ->and($job->target)->toBeNull();
});

test('serialization from a legacy job without target', function () {
    $job = new FeedJob(FullFeed::class);

    unset($job->target);

    $job = unserialize(serialize($job));

    expect($job)->toBeInstanceOf(FeedJob::class)
        ->and($job->feedClass)->toBe(FullFeed::class)
        ->and($job->target)->toBeNull()
        ->and($job->uniqueId())->toBe(FullFeed::class);
});

test('serialization with target', function () {
    $job = unserialize(serialize(new FeedJob(
        FullFeed::class,
        new FeedTarget('42', [
            'partner_id' => 42,
            'locale'     => 'en-US',
        ])
    )));

    expect($job)->toBeInstanceOf(FeedJob::class)
        ->and($job->feedClass)->toBe(FullFeed::class)
        ->and($job->target)->toBeInstanceOf(FeedTarget::class)
        ->and($job->target->key)->toBe('42')
        ->and($job->target->parameters)->toBe([
            'partner_id' => 42,
            'locale'     => 'en-US',
        ]);
});

test('generation without target', function () {
    $feed = app(FullFeed::class);

    app()->instance(FullFeed::class, $feed);

    $generator = mock(GeneratorService::class);
    $generator->shouldReceive('feed')
        ->once()
        ->with($feed)
        ->andReturn(new GenerationResultData([], []));

    (new FeedJob(FullFeed::class))->handle($generator);
});

test('generation with target uses a configured clone', function () {
    $feed   = app(FullFeed::class);
    $target = new FeedTarget('42', ['partner_id' => 42]);

    app()->instance(FullFeed::class, $feed);

    $generated = null;

    $generator = mock(GeneratorService::class);
    $generator->shouldReceive('feed')
        ->once()
        ->withArgs(function (FullFeed $resolved) use (&$generated) {
            $generated = $resolved;

            return true;
        })
        ->andReturn(new GenerationResultData([], []));

    (new FeedJob(FullFeed::class, $target))->handle($generator);

    expect($generated)->toBeInstanceOf(FullFeed::class)
        ->not->toBe($feed)
        ->and($generated->target())->toBe($target);

    expect(fn () => $feed->target())
        ->toThrow(LogicException::class, 'Feed [' . FullFeed::class . '] has no target.');
});

test('unique id preserves the legacy feed id and hashes targeted jobs using xxh128', function () {
    $plain          = new FeedJob(FullFeed::class);
    $plainDuplicate = new FeedJob(FullFeed::class);
    $target         = new FeedJob(FullFeed::class, new FeedTarget('42', ['partner_id' => 42]));
    $sameTarget     = new FeedJob(FullFeed::class, new FeedTarget('42', ['partner_id' => 81]));
    $otherTarget    = new FeedJob(FullFeed::class, new FeedTarget('81', ['partner_id' => 42]));
    $otherFeed      = new FeedJob(SitemapFeed::class, new FeedTarget('42', ['partner_id' => 42]));
    $defaultTarget  = new FeedJob(FullFeed::class, new FeedTarget('default'));

    expect($plain->uniqueId())
        ->toBe(FullFeed::class)
        ->toBe($plainDuplicate->uniqueId())
        ->not->toBe($defaultTarget->uniqueId());

    expect($target->uniqueId())
        ->toBe(hash('xxh128', FullFeed::class . "\0target\0" . '42'))
        ->toBe($sameTarget->uniqueId())
        ->not->toBe($otherTarget->uniqueId())
        ->not->toBe($otherFeed->uniqueId());

    expect($otherTarget->uniqueId())
        ->toBe(hash('xxh128', FullFeed::class . "\0target\0" . '81'))
        ->and($otherFeed->uniqueId())
        ->toBe(hash('xxh128', SitemapFeed::class . "\0target\0" . '42'));
});
