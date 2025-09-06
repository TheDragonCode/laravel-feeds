<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Enums\FeedFormatEnum;
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
})->with([
    'null => allowed'               => [null, true],
    'FeedFormatEnum::Xml => deny'   => [FeedFormatEnum::Xml, false],
    'FeedFormatEnum::class => deny' => [FeedFormatEnum::class, false],
    'bool true => deny'             => [true, false],
    'bool false => deny'            => [false, false],
    'string "true" => deny'         => ['true', false],
    'string "false" => deny'        => ['false', false],
    'string "0" => deny'            => ['0', false],
    'string "1" => deny'            => ['1', false],
    'int 0 => deny'                 => [0, false],
    'int 1 => deny'                 => [1, false],
    'string "foo" => deny'          => ['foo', false],
]);
