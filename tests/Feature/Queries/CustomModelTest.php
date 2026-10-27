<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Exceptions\FeedNotFoundException;
use DragonCode\LaravelFeed\Queries\FeedQuery;
use Workbench\App\Feeds\EmptyFeed;
use Workbench\App\Feeds\FullFeed;
use Workbench\App\Models\Feed;

use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    config()->set('feeds.model', Feed::class);

    Feed::query()->forceDelete();
});

test('creates the configured model with extra attributes', function () {
    $feed = app(FeedQuery::class)->create(
        class     : EmptyFeed::class,
        title     : 'Some',
        expression: '0 */12 * * *',
        extra     : [
            'class'      => FullFeed::class,
            'title'      => 'Other',
            'expression' => '* * * * *',
            'is_active'  => false,
            'is_foo'     => true,
            'is_bar'     => true,
        ]
    );

    expect($feed)
        ->toBeInstanceOf(Feed::class)
        ->class->toBe(FullFeed::class)
        ->title->toBe('Other')
        ->slug->toBe('other')
        ->expression->toBe('* * * * *')
        ->is_active->toBeFalse()
        ->is_foo->toBeTrue()
        ->is_bar->toBeTrue();

    assertDatabaseHas(Feed::class, [
        'class'  => FullFeed::class,
        'is_foo' => true,
        'is_bar' => true,
    ]);
});

test('supports direct creation through the custom model', function () {
    $feed = Feed::create([
        'class'      => FullFeed::class,
        'title'      => 'Some',
        'expression' => '0 */12 * * *',
        'is_active'  => true,
        'is_foo'     => true,
        'is_bar'     => true,
    ]);

    expect($feed)
        ->toBeInstanceOf(Feed::class)
        ->slug->toBe('some')
        ->is_foo->toBeTrue()
        ->is_bar->toBeTrue();
});

test('uses the configured model for every query operation', function () {
    $query = app(FeedQuery::class);

    $active = $query->create(
        class: EmptyFeed::class,
        title: 'Active'
    );

    $inactive = $query->create(
        class   : FullFeed::class,
        title   : 'Inactive',
        isActive: false
    );

    expect($query->find($active->id))
        ->toBeInstanceOf(Feed::class)
        ->id->toBe($active->id)
        ->and($query->all()->get())
        ->toHaveCount(2)
        ->each->toBeInstanceOf(Feed::class)
        ->and($query->active()->get())
        ->toHaveCount(1)
        ->each->toBeInstanceOf(Feed::class);

    $query->setLastActivity(EmptyFeed::class);

    expect($active->fresh()->last_activity)
        ->toEqual(now())
        ->and($inactive->fresh()->last_activity)
        ->toBeNull();

    $query->delete($active->id);

    expect(Feed::withTrashed()->findOrFail($active->id)->trashed())->toBeTrue();

    $query->restore($active->id);

    expect(Feed::findOrFail($active->id)->trashed())->toBeFalse();

    $query->deleteByClass(EmptyFeed::class);

    expect(Feed::withTrashed()->pluck('id')->all())->toBe([$inactive->id]);
});

test('rejects missing and soft-deleted registration IDs', function () {
    $query = app(FeedQuery::class);
    $feed  = $query->create(EmptyFeed::class, 'Deleted');

    $query->delete($feed->id);

    expect(fn () => $query->find($feed->id))
        ->toThrow(FeedNotFoundException::class, 'Feed [' . $feed->id . '] not found.')
        ->and(fn () => $query->find($feed->id + 1))
        ->toThrow(FeedNotFoundException::class, 'Feed [' . ($feed->id + 1) . '] not found.');
});

test('permanently deletes only the matching soft-deleted registration', function () {
    $query = app(FeedQuery::class);

    $deleted = $query->create(EmptyFeed::class, 'Deleted');
    $other   = $query->create(FullFeed::class, 'Other');

    $query->delete($deleted->id);
    $query->delete($other->id);

    $query->deleteByClass(EmptyFeed::class);

    expect(Feed::withTrashed()->pluck('id')->all())->toBe([$other->id]);
});
