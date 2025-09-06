<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Transformers\SpecialCharsTransformer;

test('allow', function (mixed $value) {
    $transformer = new SpecialCharsTransformer;

    expect($transformer->allow($value))->toBeTrue();
})->with([
    'simple string'    => ['Hello'],
    'string with html' => ['<b>&"\'</b>'],
    'null'             => [null],
    'int'              => [123],
    'bool true'        => [true],
    'bool false'       => [false],
    'emoji'            => ['😀'],
]);

test('transform escapes html special chars', function () {
    $transformer = new SpecialCharsTransformer;

    $value = '<b>Tom & "Jerry"\'</b>';

    expect($transformer->transform($value))->toBe('&lt;b&gt;Tom &amp; &quot;Jerry&quot;&#039;&lt;/b&gt;');
});

test('transform removes ASCII control characters', function () {
    $transformer = new SpecialCharsTransformer;

    $value = "Hello\x00\x01\x02 World\x7F!";

    expect($transformer->transform($value))->toBe('Hello World!');
});

test('transform keeps multibyte and common chars', function () {
    $transformer = new SpecialCharsTransformer;

    $value = 'Привет, мир 😀 <tag>';

    expect($transformer->transform($value))->toBe('Привет, мир 😀 &lt;tag&gt;');
});
