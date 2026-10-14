<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Exceptions\WriteFeedException;

test('updates the write path without losing exception details', function (
    ?int $written,
    ?int $expected,
    string $message,
) {
    $exception = new class ('old.xml', $written, $expected) extends WriteFeedException {};
    $trace     = $exception->getTrace();

    expect($exception->withPath('current.xml'))
        ->toBe($exception)
        ->and($exception->getMessage())
        ->toBe($message)
        ->and($exception->getTrace())
        ->toBe($trace);
})->with([
    'without byte counts' => [null, null, 'Failed to write to the feed: [current.xml].'],
    'partial write'       => [2, 6, 'Failed to write to the feed: [current.xml]. Written [2] of [6] bytes.'],
]);
