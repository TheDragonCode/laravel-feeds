<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Transformers\NullTransformer;

test('transforms null to the string "null"', function () {
    $transformer = new NullTransformer;

    expect(
        $transformer->transform(null)
    )->toBe('null');
});

test('allows only null', function (mixed $value, bool $expected) {
    $transformer = new NullTransformer;

    expect(
        $transformer->allow($value)
    )->toBe($expected);
})->with('null allow');
