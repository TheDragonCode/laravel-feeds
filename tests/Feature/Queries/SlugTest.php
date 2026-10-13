<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Exceptions\FeedNotFoundException;
use DragonCode\LaravelFeed\Models\Feed;
use DragonCode\LaravelFeed\Queries\FeedQuery;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\MultipleRecordsFoundException;
use Workbench\App\Feeds\EmptyFeed;
use Workbench\App\Feeds\FullFeed;
use Workbench\App\Models\Feed as CustomFeed;

final class SlugModernTitleFeed extends Feed
{
    protected function title(): Attribute
    {
        return Attribute::make(
            get: static fn (string $value): string => 'Display: ' . $value,
            set: static fn (string $value): string => 'Stored: ' . trim($value),
        );
    }
}

final class SlugLegacyTitleFeed extends Feed
{
    public function getTitleAttribute(string $value): string
    {
        return 'Display: ' . $value;
    }

    public function setTitleAttribute(string $value): void
    {
        $this->attributes['title'] = 'Stored: ' . trim($value);
    }
}

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

test('derives the slug from the stored custom title', function (string $model) {
    config()->set('feeds.model', $model);

    $feed = app(FeedQuery::class)->create(EmptyFeed::class, '  Product Catalog  ');

    expect($feed->fresh()->getRawOriginal('title'))->toBe('Stored: Product Catalog')
        ->and($feed->fresh()->title)->toBe('Display: Stored: Product Catalog')
        ->and($feed->fresh()->slug)->toBe('stored-product-catalog');

    $feed->update(['title' => '  Updated Catalog  ']);

    expect($feed->fresh()->getRawOriginal('title'))->toBe('Stored: Updated Catalog')
        ->and($feed->fresh()->slug)->toBe('stored-updated-catalog');

    $feed->title = '  Quiet Catalog  ';
    $feed->saveQuietly();

    expect($feed->fresh()->getRawOriginal('title'))->toBe('Stored: Quiet Catalog')
        ->and($feed->fresh()->slug)->toBe('stored-quiet-catalog');
})->with([
    'modern' => SlugModernTitleFeed::class,
    'legacy' => SlugLegacyTitleFeed::class,
]);

test('derives the slug after title changes in model listeners', function (string $event) {
    $feed = $event === 'updating'
        ? app(FeedQuery::class)->create(EmptyFeed::class, 'Original Catalog')
        : new Feed(['class' => EmptyFeed::class, 'title' => 'Original Catalog']);

    $feed->title = 'Input Catalog';

    Feed::$event(static function (Feed $feed): void {
        $feed->setRawAttributes([...$feed->getAttributes(), 'title' => 'Listener Catalog']);
    });

    try {
        $feed->save();

        expect($feed->fresh()->getRawOriginal('title'))->toBe('Listener Catalog')
            ->and($feed->fresh()->slug)->toBe('listener-catalog');
    } finally {
        Feed::getEventDispatcher()->forget('eloquent.' . $event . ': ' . Feed::class);
    }
})->with(['saving', 'creating', 'updating']);

test('preserves the stored slug when saving a model without its title selected', function () {
    $feed = app(FeedQuery::class)->create(EmptyFeed::class, 'Product Catalog');

    Feed::query()->select(['id', 'is_active'])->findOrFail($feed->id)->update(['is_active' => false]);

    expect($feed->fresh()->slug)->toBe('product-catalog');
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
