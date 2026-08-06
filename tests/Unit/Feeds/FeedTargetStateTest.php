<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Feeds\FeedTarget;
use Tests\Support\TargetedFeed;

test('configures a cloned feed without mutating the definition', function () {
    $feed   = app(TargetedFeed::class);
    $target = new FeedTarget('42', ['partner_id' => 42]);

    $configured = $feed->forTarget($target);

    expect($configured)
        ->toBeInstanceOf(TargetedFeed::class)
        ->not->toBe($feed)
        ->and($configured->target())
        ->toBe($target)
        ->and(fn () => $feed->target())
        ->toThrow(
            LogicException::class,
            'Feed [Tests\Support\TargetedFeed] has no target.'
        );
});

test('throws when a feed has no target', function () {
    expect(fn () => app(TargetedFeed::class)->target())
        ->toThrow(
            LogicException::class,
            'Feed [Tests\Support\TargetedFeed] has no target.'
        );
});

test('resets the memoized filename for each target clone', function () {
    $first = app(TargetedFeed::class)->forTarget(
        new FeedTarget('42', ['partner_id' => 42])
    );

    expect($first->filename())->toBe('partners/42.xml');

    $second = $first->forTarget(
        new FeedTarget('81', ['partner_id' => 81])
    );

    expect($first->target()->key)
        ->toBe('42')
        ->and($first->filename())
        ->toBe('partners/42.xml')
        ->and($second->target()->key)
        ->toBe('81')
        ->and($second->filename())
        ->toBe('partners/81.xml');
});
