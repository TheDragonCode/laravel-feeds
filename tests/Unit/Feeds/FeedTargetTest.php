<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Feeds\FeedTarget;

test('rejects an empty key', function (string $key) {
    expect(fn () => new FeedTarget($key))
        ->toThrow(InvalidArgumentException::class, 'Feed target key cannot be empty.');
})->with([
    'empty'      => '',
    'spaces'     => '   ',
    'whitespace' => "\t\n",
]);

test('keeps key and parameters readonly', function () {
    $target = new FeedTarget('42', [
        'partner_id' => 42,
        'locale'     => 'en-US',
    ]);

    expect($target->key)
        ->toBe('42')
        ->and($target->parameters)
        ->toBe([
            'partner_id' => 42,
            'locale'     => 'en-US',
        ])
        ->and(fn () => $target->key = '81')
        ->toThrow(Error::class)
        ->and(fn () => $target->parameters['partner_id'] = 81)
        ->toThrow(Error::class);
});

test('survives serialization', function () {
    $target = new FeedTarget('42', [
        'partner_id' => 42,
        'locale'     => 'en-US',
    ]);

    $restored = unserialize(serialize($target));

    expect($restored)
        ->toBeInstanceOf(FeedTarget::class)
        ->and($restored->key)
        ->toBe($target->key)
        ->and($restored->parameters)
        ->toBe($target->parameters);
});

test('is extensible', function () {
    $target = new class ('extended', ['value' => 1]) extends FeedTarget {};

    expect((new ReflectionClass(FeedTarget::class))->isFinal())
        ->toBeFalse()
        ->and($target)
        ->toBeInstanceOf(FeedTarget::class)
        ->and($target->key)
        ->toBe('extended')
        ->and($target->parameters)
        ->toBe(['value' => 1]);
});
