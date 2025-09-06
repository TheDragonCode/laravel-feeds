<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Transformers\SpecialCharsTransformer;

test('allows any scalar or null value', function (mixed $value) {
    $transformer = new SpecialCharsTransformer;

    expect($transformer->allow($value))->toBeTrue();
})->with('special chars allow');

test('escapes HTML special characters', function () {
    $transformer = new SpecialCharsTransformer;

    $value = '<b>Tom & "Jerry"\'</b>';

    expect($transformer->transform($value))->toBe('&lt;b&gt;Tom &amp; &quot;Jerry&quot;&#039;&lt;/b&gt;');
});

test('removes ASCII control characters', function () {
    $transformer = new SpecialCharsTransformer;

    $value = "Hello\x00\x01\x02 World\x7F!";

    expect($transformer->transform($value))->toBe('Hello World!');
});

test('preserves multibyte characters and encodes HTML brackets', function () {
    $transformer = new SpecialCharsTransformer;

    $value = 'Привет, мир 😀 <tag>';

    expect($transformer->transform($value))->toBe('Привет, мир 😀 &lt;tag&gt;');
});
