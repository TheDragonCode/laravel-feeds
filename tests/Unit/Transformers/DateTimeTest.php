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
    'DateTime default'          => [new DateTime('2025-09-06 03:07:04'), '2025-09-06T03:07:04+00:00'],
    'DateTimeImmutable default' => [new DateTimeImmutable('2025-09-06 03:07:04'), '2025-09-06T03:07:04+00:00'],
    'Carbon default'            => [Carbon::parse('2025-09-06 03:07:04'), '2025-09-06T03:07:04+00:00'],
]);

test('respects custom date format from config', function (DateTimeInterface $value, string $expected) {
    config()?->set('feeds.date.format', 'H_i_s : Y-d-m');

    $transformer = new DateTimeTransformer;

    expect(
        $transformer->transform($value)
    )->toBe($expected);
})->with([
    'DateTime custom format'          => [new DateTime('2025-09-06 03:07:04'), '03_07_04 : 2025-06-09'],
    'DateTimeImmutable custom format' => [new DateTimeImmutable('2025-09-06 03:07:04'), '03_07_04 : 2025-06-09'],
    'Carbon custom format'            => [Carbon::parse('2025-09-06 03:07:04'), '03_07_04 : 2025-06-09'],
]);

test('applies custom timezone from config when formatting', function (DateTimeInterface $value, string $expected) {
    config()?->set('feeds.date.timezone', '+12:00');

    $transformer = new DateTimeTransformer;

    expect(
        $transformer->transform($value)
    )->toBe($expected);
})->with([
    'DateTime TZ +12:00'          => [new DateTime('2025-09-06 03:07:04'), '2025-09-06T15:07:04+12:00'],
    'DateTimeImmutable TZ +12:00' => [new DateTimeImmutable('2025-09-06 03:07:04'), '2025-09-06T15:07:04+12:00'],
    'Carbon TZ +12:00'            => [Carbon::parse('2025-09-06 03:07:04'), '2025-09-06T15:07:04+12:00'],
]);

test('allows only DateTimeInterface values', function (mixed $value, bool $expected) {
    $transformer = new DateTimeTransformer;

    expect(
        $transformer->allow($value)
    )->toBe($expected);
})->with([
    'DateTime => allowed'           => [new DateTime, true],
    'DateTimeImmutable => allowed'  => [new DateTimeImmutable, true],
    'Carbon => allowed'             => [Carbon::now(), true],
    'FeedFormatEnum::Xml => deny'   => [FeedFormatEnum::Xml, false],
    'FeedFormatEnum::class => deny' => [FeedFormatEnum::class, false],
    'string "0" => deny'            => ['0', false],
    'string "1" => deny'            => ['1', false],
    'int 0 => deny'                 => [0, false],
    'int 1 => deny'                 => [1, false],
    'string "foo" => deny'          => ['foo', false],
    'null => deny'                  => [null, false],
]);
