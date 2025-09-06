<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Enums\FeedFormatEnum;
use DragonCode\LaravelFeed\Transformers\EnumTransformer;
use Workbench\App\Enums\FooEnum;

test('transform', function (UnitEnum $value, string $expected) {
    $transformer = new EnumTransformer;

    expect(
        $transformer->transform($value)
    )->toBe($expected);
})->with([
    [FeedFormatEnum::Xml, 'xml'],
    [FooEnum::Foo, 'Foo'],
]);

test('allow', function (mixed $value, bool $expected) {
    $transformer = new EnumTransformer;

    expect(
        $transformer->allow($value)
    )->toBe($expected);
})->with([
    [FeedFormatEnum::Xml, true],
    [FeedFormatEnum::class, false],
    [FooEnum::Foo, true],
    [FooEnum::class, false],
    [true, false],
    [false, false],
    ['true', false],
    ['false', false],
    ['0', false],
    ['1', false],
    [0, false],
    [1, false],
    ['foo', false],
    [null, false],
]);
