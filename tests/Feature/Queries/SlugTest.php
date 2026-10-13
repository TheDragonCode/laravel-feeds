<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Exceptions\FeedNotFoundException;
use DragonCode\LaravelFeed\Models\Feed;
use DragonCode\LaravelFeed\Queries\FeedQuery;
use Illuminate\Database\MultipleRecordsFoundException;
use Workbench\App\Feeds\EmptyFeed;
use Workbench\App\Feeds\FullFeed;
use Workbench\App\Models\Feed as CustomFeed;

beforeEach(function () {
    Feed::query()->forceDelete();
});

test('persists a slug derived from the title', function (string $title, string $slug) {
    $feed = Feed::create([
        'class' => EmptyFeed::class,
        'title' => $title,
    ]);

    expect($feed->fresh()->slug)->toBe($slug);
})->with([
    ['Product Catalog', 'product-catalog'],
    ['Каталог товаров', 'katalog-tovarov'],
    ['  Products & Offers!  ', 'products-offers'],
    ['001', '001'],
    [str_repeat('ß', 255), str_repeat('ss', 255)],
]);

test('updates the persisted slug when the title changes', function () {
    $feed = app(FeedQuery::class)->create(EmptyFeed::class, 'Old Title');

    $feed->update(['title' => 'New Title']);

    expect($feed->fresh()->slug)->toBe('new-title')
        ->and(app(FeedQuery::class)->findBySlug('new-title')->id)->toBe($feed->id)
        ->and(fn () => app(FeedQuery::class)->findBySlug('old-title'))
        ->toThrow(FeedNotFoundException::class, 'Feed [old-title] not found.');
});

test('derives the slug for custom models and quiet saves', function () {
    config()->set('feeds.model', CustomFeed::class);

    $feed = app(FeedQuery::class)->create(EmptyFeed::class, 'Original', extra: ['title' => 'Overridden']);

    expect($feed->fresh()->slug)->toBe('overridden')
        ->and(app(FeedQuery::class)->findBySlug('overridden'))->toBeInstanceOf(CustomFeed::class);

    $feed->title = 'Quiet Update';
    $feed->saveQuietly();

    expect($feed->fresh()->slug)->toBe('quiet-update');
});

test('preserves the slug when updating other registration attributes', function () {
    $feed = app(FeedQuery::class)->create(EmptyFeed::class, 'Product Catalog');

    $feed->update(['is_active' => false]);
    app(FeedQuery::class)->setLastActivity(EmptyFeed::class);

    expect($feed->fresh()->slug)->toBe('product-catalog');
});

test('does not find a deleted feed by slug', function () {
    $feed = app(FeedQuery::class)->create(EmptyFeed::class, 'Product Catalog');

    $feed->delete();

    expect(fn () => app(FeedQuery::class)->findBySlug('product-catalog'))
        ->toThrow(FeedNotFoundException::class, 'Feed [product-catalog] not found.');
});

test('rejects ambiguous slugs without restricting duplicate titles', function () {
    app(FeedQuery::class)->create(EmptyFeed::class, 'Product Catalog');
    app(FeedQuery::class)->create(FullFeed::class, 'Product-Catalog');

    expect(Feed::query()->count())->toBe(2)
        ->and(fn () => app(FeedQuery::class)->findBySlug('product-catalog'))
        ->toThrow(MultipleRecordsFoundException::class);
});
