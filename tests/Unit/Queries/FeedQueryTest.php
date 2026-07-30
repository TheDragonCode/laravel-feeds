<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Exceptions\InvalidFeedModelException;
use DragonCode\LaravelFeed\Models\Feed;
use DragonCode\LaravelFeed\Queries\FeedQuery;

test('uses the package feed model by default', function () {
    expect(config('feeds.model'))->toBe(Feed::class);
});

test('supports direct construction', function () {
    expect(new FeedQuery)->toBeInstanceOf(FeedQuery::class);
});

test('falls back to the package model when the configuration key is missing', function () {
    $feeds = config('feeds');

    unset($feeds['model']);

    config()->set('feeds', $feeds);

    expect(app(FeedQuery::class)->all()->getModel())->toBeInstanceOf(Feed::class);
});

test('rejects an invalid configured feed model', function (mixed $model) {
    config()->set('feeds.model', $model);

    expect(fn () => app(FeedQuery::class)->all())
        ->toThrow(InvalidFeedModelException::class);
})->with([
    'null'          => [null],
    'array'         => [[]],
    'another class' => [stdClass::class],
    'missing class' => ['App\Models\MissingFeed'],
]);
