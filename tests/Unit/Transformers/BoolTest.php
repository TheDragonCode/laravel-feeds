<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Transformers\BoolTransformer;

test('transforms boolean to string', function (bool $value, string $expected) {
    $transformer = new BoolTransformer;

    expect(
        $transformer->transform($value)
    )->toBe($expected);
})->with([
    [true, 'true'],
    [false, 'false'],
]);

test('allows only booleans', function (mixed $value, bool $expected) {
    $transformer = new BoolTransformer;

    expect(
        $transformer->allow($value)
    )->toBe($expected);
})->with([
    [true, true],
    [false, true],
    ['true', false],
    ['false', false],
    ['0', false],
    ['1', false],
    [0, false],
    [1, false],
    ['foo', false],
    [null, false],
]);
