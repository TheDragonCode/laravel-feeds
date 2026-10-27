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

test('preserves an explicit slug when creating a feed', function (bool $throughQuery) {
    $attributes = [
        'class' => EmptyFeed::class,
        'title' => 'Product Catalog',
        'slug'  => '  Custom Catalog!  ',
    ];

    $feed = $throughQuery
        ? app(FeedQuery::class)->create(EmptyFeed::class, 'Product Catalog', extra: $attributes)
        : Feed::create($attributes);

    expect($feed->fresh()->slug)->toBe('custom-catalog')
        ->and(app(FeedQuery::class)->findBySlug('custom-catalog')->id)->toBe($feed->id);
})->with([
    'model' => false,
    'query' => true,
]);

test('preserves an explicit slug when updating a feed', function (array $attributes) {
    $feed = app(FeedQuery::class)->create(EmptyFeed::class, 'Product Catalog');

    $feed->update($attributes);

    expect($feed->fresh()->slug)->toBe('custom-catalog')
        ->and(app(FeedQuery::class)->findBySlug('custom-catalog')->id)->toBe($feed->id)
        ->and(fn () => app(FeedQuery::class)->findBySlug('product-catalog'))
        ->toThrow(FeedNotFoundException::class, 'Feed [product-catalog] not found.');
})->with([
    'slug only'      => [['slug' => '  Custom Catalog!  ']],
    'slug and title' => [['slug' => '  Custom Catalog!  ', 'title' => 'Updated Catalog']],
]);

test('updates the persisted slug when the title changes', function () {
    $feed = app(FeedQuery::class)->create(EmptyFeed::class, 'Old Title');

    $feed->update(['title' => 'New Title']);

    expect($feed->fresh()->slug)->toBe('new-title')
        ->and(app(FeedQuery::class)->findBySlug('new-title')->id)->toBe($feed->id)
        ->and(fn () => app(FeedQuery::class)->findBySlug('old-title'))
        ->toThrow(FeedNotFoundException::class, 'Feed [old-title] not found.');

    $feed->update(['title' => 'Latest Title']);

    expect($feed->fresh()->slug)->toBe('latest-title');
});

test('derives the slug for custom models and skips quiet saves', function () {
    config()->set('feeds.model', CustomFeed::class);

    $feed = app(FeedQuery::class)->create(EmptyFeed::class, 'Original', extra: ['title' => 'Overridden']);

    expect($feed->fresh()->slug)->toBe('overridden')
        ->and(app(FeedQuery::class)->findBySlug('overridden'))->toBeInstanceOf(CustomFeed::class);

    $feed->title = 'Quiet Update';
    $feed->saveQuietly();

    expect($feed->fresh()->title)->toBe('Quiet Update')
        ->and($feed->fresh()->slug)->toBe('overridden');
});

test('derives the slug from the custom title accessor', function (string $model) {
    config()->set('feeds.model', $model);

    $feed = app(FeedQuery::class)->create(EmptyFeed::class, '  Product Catalog  ');

    expect($feed->fresh()->getRawOriginal('title'))->toBe('Stored: Product Catalog')
        ->and($feed->fresh()->title)->toBe('Display: Stored: Product Catalog')
        ->and($feed->fresh()->slug)->toBe('display-stored-product-catalog');

    $feed->update(['title' => '  Updated Catalog  ']);

    expect($feed->fresh()->getRawOriginal('title'))->toBe('Stored: Updated Catalog')
        ->and($feed->fresh()->slug)->toBe('display-stored-updated-catalog');

    $feed->title = '  Quiet Catalog  ';
    $feed->saveQuietly();

    expect($feed->fresh()->getRawOriginal('title'))->toBe('Stored: Quiet Catalog')
        ->and($feed->fresh()->slug)->toBe('display-stored-updated-catalog');
})->with([
    'modern' => SlugModernTitleFeed::class,
    'legacy' => SlugLegacyTitleFeed::class,
]);

test('derives the slug before later model listeners run', function (string $event) {
    $feed = $event === 'updating'
        ? app(FeedQuery::class)->create(EmptyFeed::class, 'Original Catalog')
        : new Feed(['class' => EmptyFeed::class, 'title' => 'Original Catalog']);

    $feed->title = 'Input Catalog';

    $dispatcher = Feed::getEventDispatcher();

    Feed::setEventDispatcher(clone $dispatcher);

    try {
        Feed::$event(static function (Feed $feed): void {
            $feed->setRawAttributes([...$feed->getAttributes(), 'title' => 'Listener Catalog']);
        });

        $feed->save();

        expect($feed->fresh()->getRawOriginal('title'))->toBe('Listener Catalog')
            ->and($feed->fresh()->slug)->toBe('input-catalog');
    } finally {
        Feed::setEventDispatcher($dispatcher);
    }
})->with(['saving', 'creating', 'updating']);

test('preserves the slug when saving a partial model with its title selected', function () {
    $feed = app(FeedQuery::class)->create(EmptyFeed::class, 'Product Catalog');

    Feed::query()->select(['id', 'title', 'is_active'])->findOrFail($feed->id)->update(['is_active' => false]);

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
