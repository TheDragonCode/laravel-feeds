<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Transformers\EnumTransformer;

test('transforms enum to string representation', function (UnitEnum $value, string $expected) {
    $transformer = new EnumTransformer;

    expect(
        $transformer->transform($value)
    )->toBe($expected);
})->with('enum transform');

test('allows only UnitEnum instances', function (mixed $value, bool $expected) {
    $transformer = new EnumTransformer;

    expect(
        $transformer->allow($value)
    )->toBe($expected);
})->with('enum allow');
