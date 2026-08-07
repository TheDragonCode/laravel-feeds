<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Feeds\FeedTarget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Application;
use Tests\Support\TargetedFeed;
use Workbench\App\Models\User;

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

test('preserves subclass properties named target', function () {
    $feed = new class (app(Application::class)) extends TargetedFeed {
        protected string $target = 'custom';

        public function customTarget(): string
        {
            return $this->target;
        }
    };

    $configured = $feed->forTarget(new FeedTarget('42'));

    expect($configured->customTarget())
        ->toBe('custom')
        ->and($configured->target()->key)
        ->toBe('42');
});

test('preserves ordinary feed methods named target and for target', function () {
    $feed = new class (app(Application::class)) extends Feed {
        public function target(string $value): string
        {
            return $value;
        }

        public function forTarget(): string
        {
            return 'custom';
        }

        public function builder(): Builder
        {
            return User::query();
        }
    };

    expect($feed->target('value'))
        ->toBe('value')
        ->and($feed->forTarget())
        ->toBe('custom');
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
