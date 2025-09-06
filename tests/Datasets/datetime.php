<?php

declare(strict_types=1);

use Carbon\Carbon;
use DragonCode\LaravelFeed\Enums\FeedFormatEnum;

dataset('datetime default', [
    'DateTime default'          => [new DateTime('2025-09-06 03:07:04'), '2025-09-06T03:07:04+00:00'],
    'DateTimeImmutable default' => [new DateTimeImmutable('2025-09-06 03:07:04'), '2025-09-06T03:07:04+00:00'],
    'Carbon default'            => [Carbon::parse('2025-09-06 03:07:04'), '2025-09-06T03:07:04+00:00'],
]);

dataset('datetime format', [
    'DateTime custom format'          => [new DateTime('2025-09-06 03:07:04'), '03_07_04 : 2025-06-09'],
    'DateTimeImmutable custom format' => [new DateTimeImmutable('2025-09-06 03:07:04'), '03_07_04 : 2025-06-09'],
    'Carbon custom format'            => [Carbon::parse('2025-09-06 03:07:04'), '03_07_04 : 2025-06-09'],
]);

dataset('datetime timezone', [
    'DateTime TZ +12:00'          => [new DateTime('2025-09-06 03:07:04'), '2025-09-06T15:07:04+12:00'],
    'DateTimeImmutable TZ +12:00' => [new DateTimeImmutable('2025-09-06 03:07:04'), '2025-09-06T15:07:04+12:00'],
    'Carbon TZ +12:00'            => [Carbon::parse('2025-09-06 03:07:04'), '2025-09-06T15:07:04+12:00'],
]);

dataset('datetime allow', [
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
