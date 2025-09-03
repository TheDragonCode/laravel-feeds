<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Exceptions\InvalidExpressionException;
use DragonCode\LaravelFeed\Queries\FeedQuery;
use Workbench\App\Feeds\EmptyFeed;

test('not a class', function (string $value) {
    app(FeedQuery::class)->create(
        class     : EmptyFeed::class,
        title     : 'Some',
        expression: $value
    );
})->throws(
    exception: InvalidExpressionException::class,
)->with([
    'foo',
    '123',
    'foo 1 2 3',
    '* * * * * *',
    '* * *',
]);
