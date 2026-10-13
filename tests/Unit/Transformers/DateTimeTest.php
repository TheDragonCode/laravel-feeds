<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Transformers\DateTimeTransformer;

test('formats DateTimeInterface to ATOM in UTC by default', function (DateTimeInterface $value, string $expected) {
    $transformer = new DateTimeTransformer;

    expect(
        $transformer->transform($value)
    )->toBe($expected);
})->with('datetime default');

test('respects custom date format from config', function (DateTimeInterface $value, string $expected) {
    config()?->set('feeds.date.format', 'H_i_s : Y-d-m');

    $transformer = new DateTimeTransformer;

    expect(
        $transformer->transform($value)
    )->toBe($expected);
})->with('datetime format');

test('applies custom timezone from config when formatting', function (DateTimeInterface $value, string $expected) {
    config()?->set('feeds.date.timezone', '+12:00');

    $transformer = new DateTimeTransformer;

    expect(
        $transformer->transform($value)
    )->toBe($expected);
})->with('datetime timezone');

test('allows only DateTimeInterface values', function (mixed $value, bool $expected) {
    $transformer = new DateTimeTransformer;

    expect(
        $transformer->allow($value)
    )->toBe($expected);
})->with('datetime allow');

test('supports subclasses whose constructor does not call the parent', function () {
    $transformer = new class extends DateTimeTransformer {
        public function __construct() {}
    };
    $date = new DateTimeImmutable('2026-07-28T12:30:00+00:00');

    expect($transformer->transform($date))->toBe('2026-07-28T12:30:00+00:00');

    config()->set('feeds.date.format', 'Y-m-d');

    expect($transformer->transform($date))->toBe('2026-07-28');
});
