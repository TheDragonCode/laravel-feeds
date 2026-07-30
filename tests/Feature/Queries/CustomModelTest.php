<?php

declare(strict_types=1);

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

    expect(Feed::withTrashed()->find($active->id))->toBeNull();
});
