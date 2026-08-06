<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Feeds\FeedTarget;
use DragonCode\LaravelFeed\Services\GeneratorService;
use Tests\Support\SplitTargetedFeed;
use Tests\Support\TargetedFeed;

test('generates different filenames for different targets', function () {
    $generator = app(GeneratorService::class);
    $feed      = app(TargetedFeed::class);
    $first     = $feed->forTarget(new FeedTarget('42', ['partner_id' => 42]));
    $second    = $feed->forTarget(new FeedTarget('81', ['partner_id' => 81]));

    $firstResult  = $generator->feed($first);
    $secondResult = $generator->feed($second);

    expect($first->storagePath())
        ->toBe('partners/42.xml')
        ->and($second->storagePath())
        ->toBe('partners/81.xml')
        ->and($firstResult->paths)
        ->toBe([$first->path()])
        ->and($secondResult->paths)
        ->toBe([$second->path()])
        ->and($first->path())
        ->not->toBe($second->path())
        ->and($first->path())
        ->toBeFile()
        ->and($second->path())
        ->toBeFile();
});

test('splits one target into multiple files with per file', function () {
    $feed = app(SplitTargetedFeed::class)
        ->forTarget(new FeedTarget('42', ['partner_id' => 42]));

    $result = app(GeneratorService::class)->feed($feed);

    expect($feed->storagePath(1))
        ->toBe('partners/42-1.xml')
        ->and($feed->storagePath(2))
        ->toBe('partners/42-2.xml')
        ->and($result->paths)
        ->toBe([$feed->path(1), $feed->path(2)])
        ->and($feed->path(1))
        ->toBeFile()
        ->and($feed->path(2))
        ->toBeFile();
});
