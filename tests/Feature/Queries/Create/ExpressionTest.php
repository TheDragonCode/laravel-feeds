<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Exceptions\UnexpectedClassException;
use DragonCode\LaravelFeed\Exceptions\UnknownFeedClassException;
use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Queries\FeedQuery;
use Workbench\App\Data\ProductFakeData;

test('not a class', function () {
    app(FeedQuery::class)->create(
        class: 'foo',
        title: 'Some',
    );
})->throws(
    exception       : UnexpectedClassException::class,
    exceptionMessage: 'Class [foo] does not exist.'
);

test('not extending', function () {
    app(FeedQuery::class)->create(
        class: ProductFakeData::class,
        title: 'Some',
    );
})->throws(
    exception       : UnknownFeedClassException::class,
    exceptionMessage: sprintf('The [%s] class must extend from the %s class.', ProductFakeData::class, Feed::class)
);
