<?php

declare(strict_types=1);

use Carbon\Carbon;
use DragonCode\LaravelFeed\Enums\FeedFormatEnum;
use DragonCode\LaravelFeed\Transformers\DateTimeTransformer;

test('formats DateTimeInterface to ATOM in UTC by default', function (DateTimeInterface $value, string $expected) {
    $transformer = new DateTimeTransformer;

    expect(
        $transformer->transform($value)
    )->toBe($expected);
})->with([
    [new DateTime('2025-09-06 03:07:04'), '2025-09-06T03:07:04+00:00'],
    [new DateTimeImmutable('2025-09-06 03:07:04'), '2025-09-06T03:07:04+00:00'],
    [Carbon::parse('2025-09-06 03:07:04'), '2025-09-06T03:07:04+00:00'],
]);

test('respects custom date format from config', function (DateTimeInterface $value, string $expected) {
    config()?->set('feeds.date.format', 'H_i_s : Y-d-m');

    $transformer = new DateTimeTransformer;

    expect(
        $transformer->transform($value)
    )->toBe($expected);
})->with([
    [new DateTime('2025-09-06 03:07:04'), '03_07_04 : 2025-06-09'],
    [new DateTimeImmutable('2025-09-06 03:07:04'), '03_07_04 : 2025-06-09'],
    [Carbon::parse('2025-09-06 03:07:04'), '03_07_04 : 2025-06-09'],
]);

test('applies custom timezone from config when formatting', function (DateTimeInterface $value, string $expected) {
    config()?->set('feeds.date.timezone', '+12:00');

    $transformer = new DateTimeTransformer;

    expect(
        $transformer->transform($value)
    )->toBe($expected);
})->with([
    [new DateTime('2025-09-06 03:07:04'), '2025-09-06T15:07:04+12:00'],
    [new DateTimeImmutable('2025-09-06 03:07:04'), '2025-09-06T15:07:04+12:00'],
    [Carbon::parse('2025-09-06 03:07:04'), '2025-09-06T15:07:04+12:00'],
]);

test('allows only DateTimeInterface values', function (mixed $value, bool $expected) {
    $transformer = new DateTimeTransformer;

    expect(
        $transformer->allow($value)
    )->toBe($expected);
})->with([
    [new DateTime, true],
    [new DateTimeImmutable, true],
    [Carbon::now(), true],
    [FeedFormatEnum::Xml, false],
    [FeedFormatEnum::class, false],
    ['0', false],
    ['1', false],
    [0, false],
    [1, false],
    ['foo', false],
    [null, false],
]);
