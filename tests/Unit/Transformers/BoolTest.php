<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Transformers\BoolTransformer;

test('transforms boolean to string', function (bool $value, string $expected) {
    $transformer = new BoolTransformer;

    expect(
        $transformer->transform($value)
    )->toBe($expected);
})->with([
    'true to "true"'   => [true, 'true'],
    'false to "false"' => [false, 'false'],
]);

test('allows only booleans', function (mixed $value, bool $expected) {
    $transformer = new BoolTransformer;

    expect(
        $transformer->allow($value)
    )->toBe($expected);
})->with([
    'bool true => allowed'   => [true, true],
    'bool false => allowed'  => [false, true],
    'string "true" => deny'  => ['true', false],
    'string "false" => deny' => ['false', false],
    'string "0" => deny'     => ['0', false],
    'string "1" => deny'     => ['1', false],
    'int 0 => deny'          => [0, false],
    'int 1 => deny'          => [1, false],
    'string "foo" => deny'   => ['foo', false],
    'null => deny'           => [null, false],
]);
