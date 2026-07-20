<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Exceptions\CloseFeedException;
use DragonCode\LaravelFeed\Exceptions\WriteFeedException;
use DragonCode\LaravelFeed\Services\FilesystemService;
use Illuminate\Contracts\Filesystem\LockTimeoutException;
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

final class ControlledMoveFilesystem extends Filesystem
{
    protected array $moveFailures = [];

    public function failNextMoveTo(string $path): void
    {
        $this->moveFailures[$path] = true;
    }

    public function move($path, $target)
    {
        if (isset($this->moveFailures[$target])) {
            unset($this->moveFailures[$target]);

            return false;
        }

        return parent::move($path, $target);
    }
}

beforeEach(function () {
    stream_wrapper_register('controlled-write', ControlledWriteStream::class);
});

afterEach(function () {
    stream_wrapper_unregister('controlled-write');

    (new Filesystem)->deleteDirectory(storage_path('framework/cache/laravel-feeds'));
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

test('creates collision-resistant drafts and cleans the staging directory', function () {
    $directory = (new TemporaryDirectory)->create();
    $path      = $directory->path('feed.json');
    $drafts    = [];

    try {
        (new FilesystemService(new Filesystem))->publish($path, function (string $staging) use (&$drafts, $path) {
            $service = new FilesystemService(new Filesystem);
            $first   = $service->createDraft('feed.json', $staging);
            $second  = $service->createDraft('feed.json', $staging);

            $drafts[] = $service->finishDraft($first);
            $drafts[] = $service->finishDraft($second);

            return [$path => $drafts[0]];
        });

        expect($drafts[0])
            ->not->toBe($drafts[1])
            ->and($path)
            ->toBeFile()
            ->and(glob($directory->path() . DIRECTORY_SEPARATOR . '.feeds_staging_*'))
            ->toBe([]);
    } finally {
        $directory->delete();
    }
});

test('prevents overlapping publication for the same feed', function () {
    $directory = (new TemporaryDirectory)->create();
    $path      = $directory->path('feed.json');
    $service   = new FilesystemService(new Filesystem);

    try {
        $service->lock($path, function () use ($path, $service) {
            expect(fn () => $service->lock($path, fn () => null, false))
                ->toThrow(LockTimeoutException::class);
        });
    } finally {
        $directory->delete();
    }
});

test('restores every published part when a staged move fails', function () {
    $directory   = (new TemporaryDirectory)->create();
    $publication = $directory->path('feed.json');
    $first       = $directory->path('feed-1.json');
    $second      = $directory->path('feed-2.json');
    $file        = new ControlledMoveFilesystem;
    $service     = new FilesystemService($file);

    file_put_contents($first, 'old-first');
    file_put_contents($second, 'old-second');

    $file->failNextMoveTo($second);

    try {
        expect(fn () => $service->publish($publication, function (string $staging) use ($first, $second, $service) {
            $firstDraft = $service->createDraft('feed.json', $staging);
            $service->append($firstDraft, 'new-first', $first);

            $secondDraft = $service->createDraft('feed.json', $staging);
            $service->append($secondDraft, 'new-second', $second);

            return [
                $first  => $service->finishDraft($firstDraft),
                $second => $service->finishDraft($secondDraft),
            ];
        }))
            ->toThrow(CloseFeedException::class, 'Unable to publish the staged feed:');

        expect(file_get_contents($first))
            ->toBe('old-first')
            ->and(file_get_contents($second))
            ->toBe('old-second')
            ->and(glob($directory->path() . DIRECTORY_SEPARATOR . '.feeds_staging_*'))
            ->toBe([]);
    } finally {
        $directory->delete();
    }
});

test('release restores the published feed and cleans its draft when move fails', function () {
    $directory   = (new TemporaryDirectory)->create();
    $publication = $directory->path('feed.json');
    $file        = new ControlledMoveFilesystem;
    $service     = new FilesystemService($file);
    $draft       = $service->createDraft('feed.json', $directory->path('drafts'));
    $draftPath   = stream_get_meta_data($draft)['uri'];

    file_put_contents($publication, 'old');

    $service->append($draft, 'new', $publication);
    $file->failNextMoveTo($publication);

    try {
        expect(fn () => $service->release($draft, $publication))
            ->toThrow(CloseFeedException::class, 'Unable to publish the staged feed:')
            ->and(file_get_contents($publication))
            ->toBe('old')
            ->and(dirname($draftPath))
            ->not->toBeDirectory();
    } finally {
        $directory->delete();
    }
});

test('removes only obsolete numeric split parts after a successful commit', function () {
    $directory   = (new TemporaryDirectory)->create();
    $publication = $directory->path('feed.json');
    $unrelated   = $directory->path('feed-copy.json');
    $service     = new FilesystemService(new Filesystem);

    foreach (range(1, 3) as $index) {
        file_put_contents($directory->path("feed-$index.json"), "old-$index");
    }

    file_put_contents($unrelated, 'unrelated');

    try {
        $service->publish($publication, function (string $staging) use ($publication, $service) {
            $draft = $service->createDraft('feed.json', $staging);
            $service->append($draft, 'new', $publication);

            return [$publication => $service->finishDraft($draft)];
        });

        expect(file_get_contents($publication))
            ->toBe('new')
            ->and($directory->path('feed-1.json'))
            ->not->toBeFile()
            ->and($directory->path('feed-2.json'))
            ->not->toBeFile()
            ->and($directory->path('feed-3.json'))
            ->not->toBeFile()
            ->and(file_get_contents($unrelated))
            ->toBe('unrelated');
    } finally {
        $directory->delete();
    }
});
