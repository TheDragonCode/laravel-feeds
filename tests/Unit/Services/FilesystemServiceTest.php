<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Exceptions\WriteFeedException;
use DragonCode\LaravelFeed\Services\FilesystemService;
use Illuminate\Filesystem\Filesystem;
use Spatie\TemporaryDirectory\TemporaryDirectory;

final class ControlledWriteStream
{
    public mixed $context;

    public static array $results = [];

    public static array $calls = [];

    public static string $content = '';

    public static function reset(false|int ...$results): void
    {
        self::$results = $results;
        self::$calls   = [];
        self::$content = '';
    }

    public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
    {
        return true;
    }

    public function stream_write(string $data): false|int
    {
        self::$calls[] = $data;

        $result = array_shift(self::$results);

        if ($result === null) {
            throw new LogicException('Missing controlled write result.');
        }

        if (is_int($result) && $result > 0) {
            self::$content .= substr($data, 0, $result);
        }

        return $result;
    }
}

beforeEach(function () {
    stream_wrapper_register('controlled-write', ControlledWriteStream::class);
});

afterEach(function () {
    stream_wrapper_unregister('controlled-write');
});

test('writes all bytes after partial stream writes', function () {
    ControlledWriteStream::reset(2, 0, 2, 0, 2);

    $resource = fopen('controlled-write://buffer', 'wb');

    try {
        (new FilesystemService(new Filesystem))->append($resource, 'abcdef', 'feed.xml');

        expect(ControlledWriteStream::$content)->toBe('abcdef');
    } finally {
        fclose($resource);
    }
});

test('throws when a stream stops making progress', function (false|int $failure) {
    ControlledWriteStream::reset(2, 0, $failure);

    $directory = (new TemporaryDirectory)->create();
    $path      = $directory->path('feed.xml');
    $resource  = fopen('controlled-write://buffer', 'wb');

    file_put_contents($path, 'published');

    try {
        expect(fn () => (new FilesystemService(new Filesystem))->append($resource, 'abcdef', $path))
            ->toThrow(
                WriteFeedException::class,
                "Failed to write to the feed: [$path]. Written [2] of [6] bytes."
            );

        expect(file_get_contents($path))->toBe('published');
    } finally {
        fclose($resource);

        $directory->delete();
    }
})->with([
    'zero'  => 0,
    'false' => false,
]);

test('treats empty content as a no-op', function () {
    ControlledWriteStream::reset(false);

    $resource = fopen('controlled-write://buffer', 'wb');

    try {
        (new FilesystemService(new Filesystem))->append($resource, '', 'feed.xml');

        expect(ControlledWriteStream::$calls)
            ->toBe([])
            ->and(ControlledWriteStream::$content)
            ->toBe('');
    } finally {
        fclose($resource);
    }
});
