<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Transformers\SpecialCharsTransformer;

test('allows any scalar or null value', function (mixed $value) {
    $transformer = new SpecialCharsTransformer;

    expect($transformer->allow($value))->toBeTrue();
})->with('special chars allow');

test('preserves XML special characters', function () {
    $transformer = new SpecialCharsTransformer;

    $value = '<b>Tom & "Jerry"\'</b>';

    expect($transformer->transform($value))->toBe($value);
});

test('removes ASCII control characters', function () {
    $transformer = new SpecialCharsTransformer;

    $value = "Hello\x00\x01\x02 World\x7F!";

    expect($transformer->transform($value))->toBe('Hello World!');
});

test('preserves multibyte characters and XML brackets', function () {
    $transformer = new SpecialCharsTransformer;

    $value = 'Привет, мир 😀 <tag>';

    expect($transformer->transform($value))->toBe($value);
});
