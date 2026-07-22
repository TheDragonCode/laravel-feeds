<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Exceptions\CloseFeedException;
use DragonCode\LaravelFeed\Exceptions\OpenFeedException;
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
    public array $moves = [];

    protected array $moveFailures = [];

    public function failNextMoveTo(string $path): void
    {
        $this->moveFailures[$path] = true;
    }

    public function move($path, $target)
    {
        $this->moves[] = [$path, $target];

        if (isset($this->moveFailures[$target])) {
            unset($this->moveFailures[$target]);

            return false;
        }

        return parent::move($path, $target);
    }
}

final class ControlledDraftNameFilesystemService extends FilesystemService
{
    public static array $filenames = [];

    public static function reset(string ...$filenames): void
    {
        self::$filenames = $filenames;
    }

    protected function temporaryFilename(string $filename): string
    {
        return array_shift(self::$filenames) ?? throw new LogicException('Missing controlled draft filename.');
    }
}

final class ControlledIdentifierFilesystemService extends FilesystemService
{
    public static array $identifiers = [];

    public static function reset(string ...$identifiers): void
    {
        self::$identifiers = $identifiers;
    }

    protected function uniqueIdentifier(): string
    {
        return array_shift(self::$identifiers) ?? throw new LogicException('Missing controlled identifier.');
    }
}

final class ControlledDraftCollisionFilesystemService extends FilesystemService
{
    protected int $attempt = 0;

    protected ?string $directoryName = null;

    public function __construct(
        Filesystem $file,
        protected string $location,
        protected int $collisions,
    ) {
        parent::__construct($file);
    }

    protected function temporaryFilename(string $filename): string
    {
        if ($this->directoryName === null) {
            return $this->directoryName = 'feeds_draft_directory_' . $this->attempt;
        }

        $filename = 'feeds_draft_file_' . $this->attempt++;

        if ($this->collisions > 0) {
            file_put_contents(
                $this->location . DIRECTORY_SEPARATOR . $this->directoryName . DIRECTORY_SEPARATOR . $filename,
                'collision'
            );

            $this->collisions--;
        }

        $this->directoryName = null;

        return $filename;
    }
}

final class MissingDraftPathFilesystemService extends FilesystemService
{
    protected function draftPath(string $filename, ?string $directory = null): string
    {
        return $directory . DIRECTORY_SEPARATOR . 'missing' . DIRECTORY_SEPARATOR . 'feeds_draft_file';
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

test('retries draft creation when the generated path already exists', function () {
    $directory = (new TemporaryDirectory)->create();
    $collision = $directory->path() . DIRECTORY_SEPARATOR . 'feeds_draft_collision';
    $sentinel  = $collision . DIRECTORY_SEPARATOR . 'sentinel.txt';

    mkdir($collision);
    file_put_contents($sentinel, 'existing');

    ControlledDraftNameFilesystemService::reset(
        'feeds_draft_collision',
        'feeds_draft_directory',
        'feeds_draft_file'
    );

    try {
        $service   = new ControlledDraftNameFilesystemService(new Filesystem);
        $draft     = $service->createDraft('feed.json', $directory->path());
        $draftPath = $service->finishDraft($draft);

        expect(basename(dirname($draftPath)))
            ->toBe('feeds_draft_directory')
            ->and(basename($draftPath))
            ->toBe('feeds_draft_file')
            ->and(file_get_contents($sentinel))
            ->toBe('existing');
    } finally {
        $directory->delete();
    }
});

test('retries exclusive draft file creation after a collision', function () {
    $directory = (new TemporaryDirectory)->create();

    try {
        $service = new ControlledDraftCollisionFilesystemService(
            new Filesystem,
            $directory->path(),
            1
        );
        $draft     = $service->createDraft('feed.json', $directory->path());
        $draftPath = $service->finishDraft($draft);

        expect(basename(dirname($draftPath)))
            ->toBe('feeds_draft_directory_1')
            ->and(basename($draftPath))
            ->toBe('feeds_draft_file_1')
            ->and($directory->path() . DIRECTORY_SEPARATOR . 'feeds_draft_directory_0')
            ->not->toBeDirectory();
    } finally {
        $directory->delete();
    }
});

test('stops retrying after repeated draft file collisions', function () {
    $directory = (new TemporaryDirectory)->create();

    try {
        $service = new ControlledDraftCollisionFilesystemService(
            new Filesystem,
            $directory->path(),
            10
        );

        expect(fn () => $service->createDraft('feed.json', $directory->path()))
            ->toThrow(
                OpenFeedException::class,
                'Unable to create a unique feed draft after [10] attempts.'
            )
            ->and(glob($directory->path() . DIRECTORY_SEPARATOR . 'feeds_draft_directory_*'))
            ->toBe([]);
    } finally {
        $directory->delete();
    }
});

test('stops retrying after repeated draft directory collisions', function () {
    $directory = (new TemporaryDirectory)->create();
    $collision = $directory->path() . DIRECTORY_SEPARATOR . 'feeds_draft_collision';
    $sentinel  = $collision . DIRECTORY_SEPARATOR . 'sentinel.txt';

    mkdir($collision);
    file_put_contents($sentinel, 'existing');

    ControlledDraftNameFilesystemService::reset(
        ...array_fill(0, 10, 'feeds_draft_collision')
    );

    try {
        $service = new ControlledDraftNameFilesystemService(new Filesystem);

        expect(fn () => $service->createDraft('feed.json', $directory->path()))
            ->toThrow(
                OpenFeedException::class,
                'Unable to create a unique temporary directory after [10] attempts.'
            )
            ->and(file_get_contents($sentinel))
            ->toBe('existing');
    } finally {
        $directory->delete();
    }
});

test('removes an allocated draft directory when filename generation fails', function () {
    $directory = (new TemporaryDirectory)->create();

    ControlledDraftNameFilesystemService::reset('feeds_draft_directory');

    try {
        $service = new ControlledDraftNameFilesystemService(new Filesystem);

        expect(fn () => $service->createDraft('feed.json', $directory->path()))
            ->toThrow(OpenFeedException::class, 'Missing controlled draft filename.')
            ->and($directory->path() . DIRECTORY_SEPARATOR . 'feeds_draft_directory')
            ->not->toBeDirectory();
    } finally {
        $directory->delete();
    }
});

test('does not retry draft creation after a non-collision failure', function () {
    $directory = (new TemporaryDirectory)->create();

    try {
        $service = new MissingDraftPathFilesystemService(new Filesystem);

        expect(fn () => $service->createDraft('feed.json', $directory->path()))
            ->toThrow(OpenFeedException::class, 'Unable to open resource for writing.');
    } finally {
        $directory->delete();
    }
});

test('keeps draft paths independent from output filenames', function () {
    $directory = (new TemporaryDirectory)->create();
    $service   = new FilesystemService(new Filesystem);

    try {
        $draft     = $service->createDraft('private-catalog.json', $directory->path());
        $draftPath = $service->finishDraft($draft);

        expect(basename(dirname($draftPath)))
            ->toMatch('/^feeds_draft_[a-f0-9]{32}$/')
            ->and(basename($draftPath))
            ->toMatch('/^feeds_draft_[a-f0-9]{32}$/')
            ->and($draftPath)
            ->not->toContain('private_catalog');
    } finally {
        $directory->delete();
    }
});

test('retries staging creation without removing the colliding directory', function () {
    $directory = (new TemporaryDirectory)->create();
    $path      = $directory->path('feed.json');
    $collision = $directory->path() . DIRECTORY_SEPARATOR . '.feeds_staging_collision';
    $sentinel  = $collision . DIRECTORY_SEPARATOR . 'sentinel.txt';

    mkdir($collision);
    file_put_contents($sentinel, 'existing');

    ControlledIdentifierFilesystemService::reset(
        'collision',
        'staging',
        'draft_directory',
        'draft_file',
        'ownership_directory',
        'ownership_file'
    );

    try {
        $service = new ControlledIdentifierFilesystemService(new Filesystem);

        $service->publish($path, function (string $staging) use ($path, $service) {
            expect(basename($staging))->toBe('.feeds_staging_staging');

            $draft = $service->createDraft('feed.json', $staging);
            $service->append($draft, 'published', $path);

            return [$path => $service->finishDraft($draft)];
        });

        expect(file_get_contents($path))
            ->toBe('published')
            ->and(file_get_contents($sentinel))
            ->toBe('existing')
            ->and(glob($directory->path() . DIRECTORY_SEPARATOR . '.feeds_staging_*'))
            ->toBe([$collision]);
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

    try {
        $service->publish($publication, function (string $staging) use ($first, $second, $service) {
            $firstDraft = $service->createDraft('feed.json', $staging);
            $service->append($firstDraft, 'old-first', $first);

            $secondDraft = $service->createDraft('feed.json', $staging);
            $service->append($secondDraft, 'old-second', $second);

            return [
                $first  => $service->finishDraft($firstDraft),
                $second => $service->finishDraft($secondDraft),
            ];
        });

        $file->failNextMoveTo($second);

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
    $drafts      = $directory->path('drafts');
    $foreign     = $drafts . DIRECTORY_SEPARATOR . 'foreign-draft';
    $file        = new ControlledMoveFilesystem;
    $service     = new FilesystemService($file);
    $draft       = $service->createDraft('feed.json', $drafts);
    $draftPath   = stream_get_meta_data($draft)['uri'];

    file_put_contents($publication, 'old');
    file_put_contents($foreign, 'foreign');

    $service->append($draft, 'new', $publication);
    $file->failNextMoveTo($publication);

    try {
        expect(fn () => $service->release($draft, $publication))
            ->toThrow(CloseFeedException::class, 'Unable to publish the staged feed:')
            ->and(file_get_contents($publication))
            ->toBe('old')
            ->and(dirname($draftPath))
            ->not->toBeDirectory()
            ->and(file_get_contents($foreign))
            ->toBe('foreign');
    } finally {
        $directory->delete();
    }
});

test('removes only obsolete numeric split parts after a successful commit', function () {
    $directory   = (new TemporaryDirectory)->create();
    $publication = $directory->path('feed.json');
    $unrelated   = $directory->path('feed-copy.json');
    $service     = new FilesystemService(new Filesystem);

    try {
        $service->publish($publication, function (string $staging) use ($directory, $service) {
            $drafts = [];

            foreach (range(1, 3) as $index) {
                $target = $directory->path("feed-$index.json");
                $draft  = $service->createDraft('feed.json', $staging);

                $service->append($draft, "old-$index", $target);

                $drafts[$target] = $service->finishDraft($draft);
            }

            return $drafts;
        });

        file_put_contents($unrelated, 'unrelated');

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

test('removes a pre-registry primary file when publishing split parts', function () {
    $directory   = (new TemporaryDirectory)->create();
    $publication = $directory->path('feed.json');
    $first       = $directory->path('feed-1.json');
    $second      = $directory->path('feed-2.json');
    $service     = new FilesystemService(new Filesystem);

    file_put_contents($publication, 'legacy-primary');

    try {
        $service->publish($publication, function (string $staging) use ($first, $second, $service) {
            $firstDraft = $service->createDraft('feed.json', $staging);
            $service->append($firstDraft, 'first', $first);

            $secondDraft = $service->createDraft('feed.json', $staging);
            $service->append($secondDraft, 'second', $second);

            return [
                $first  => $service->finishDraft($firstDraft),
                $second => $service->finishDraft($secondDraft),
            ];
        });

        expect($publication)
            ->not->toBeFile()
            ->and(file_get_contents($first))
            ->toBe('first')
            ->and(file_get_contents($second))
            ->toBe('second');
    } finally {
        $directory->delete();
    }
});

test('preserves an unowned numeric sibling and rejects it as a split target', function () {
    $directory   = (new TemporaryDirectory)->create();
    $publication = $directory->path('catalog.json');
    $independent = $directory->path('catalog-1.json');
    $file        = new ControlledMoveFilesystem;
    $service     = new FilesystemService($file);

    file_put_contents($independent, 'independent');

    try {
        $service->publish($publication, function (string $staging) use ($publication, $service) {
            $draft = $service->createDraft('catalog.json', $staging);
            $service->append($draft, 'published', $publication);

            return [$publication => $service->finishDraft($draft)];
        });

        expect($publication)
            ->toBeFile()
            ->and(file_get_contents($publication))
            ->toBe('published')
            ->and($independent)
            ->toBeFile()
            ->and(file_get_contents($independent))
            ->toBe('independent')
            ->and(array_column($file->moves, 0))
            ->not->toContain($independent);

        expect(fn () => $service->publish(
            $publication,
            function (string $staging) use ($independent, $service) {
                $draft = $service->createDraft('catalog.json', $staging);
                $service->append($draft, 'split', $independent);

                return [$independent => $service->finishDraft($draft)];
            }
        ))->toThrow(RuntimeException::class, "Feed publication target is not owned: [$independent].");

        expect(file_get_contents($publication))
            ->toBe('published')
            ->and(file_get_contents($independent))
            ->toBe('independent')
            ->and(glob($directory->path() . DIRECTORY_SEPARATOR . '.feeds_staging_*'))
            ->toBe([]);
    } finally {
        $directory->delete();
    }
});

test('rejects a primary target owned by another feed publication', function () {
    $directory   = (new TemporaryDirectory)->create();
    $publication = $directory->path('catalog.json');
    $split       = $directory->path('catalog-1.json');
    $service     = new FilesystemService(new Filesystem);

    try {
        $service->publish($publication, function (string $staging) use ($service, $split) {
            $draft = $service->createDraft('catalog.json', $staging);
            $service->append($draft, 'owned-split', $split);

            return [$split => $service->finishDraft($draft)];
        });

        expect(fn () => $service->publish($split, function (string $staging) use ($service, $split) {
            $draft = $service->createDraft('catalog-1.json', $staging);
            $service->append($draft, 'other-feed', $split);

            return [$split => $service->finishDraft($draft)];
        }))
            ->toThrow(RuntimeException::class, "Feed publication target is not owned: [$split].")
            ->and(file_get_contents($split))
            ->toBe('owned-split')
            ->and(glob($directory->path() . DIRECTORY_SEPARATOR . '.feeds_staging_*'))
            ->toBe([]);
    } finally {
        $directory->delete();
    }
});

test('preserves a primary path owned by another publication when publishing split parts', function () {
    $directory   = (new TemporaryDirectory)->create();
    $root        = $directory->path('catalog.json');
    $publication = $directory->path('catalog-1.json');
    $nested      = $directory->path('catalog-1-1.json');
    $service     = new FilesystemService(new Filesystem);

    try {
        $service->publish($root, function (string $staging) use ($publication, $service) {
            $draft = $service->createDraft('catalog.json', $staging);
            $service->append($draft, 'parent-feed', $publication);

            return [$publication => $service->finishDraft($draft)];
        });

        $service->publish($publication, function (string $staging) use ($nested, $service) {
            $draft = $service->createDraft('catalog-1.json', $staging);
            $service->append($draft, 'nested-feed', $nested);

            return [$nested => $service->finishDraft($draft)];
        });

        expect(file_get_contents($publication))
            ->toBe('parent-feed')
            ->and(file_get_contents($nested))
            ->toBe('nested-feed');
    } finally {
        $directory->delete();
    }
});

test('restores the feed and ownership registry when registry installation fails', function () {
    $directory   = (new TemporaryDirectory)->create();
    $publication = $directory->path('feed.json');
    $ownership   = $directory->path('.laravel-feeds' . DIRECTORY_SEPARATOR . 'ownership.json');
    $file        = new ControlledMoveFilesystem;
    $service     = new FilesystemService($file);

    try {
        $service->publish($publication, function (string $staging) use ($publication, $service) {
            $draft = $service->createDraft('feed.json', $staging);
            $service->append($draft, 'old', $publication);

            return [$publication => $service->finishDraft($draft)];
        });

        $file->failNextMoveTo($ownership);

        expect(fn () => $service->publish($publication, function (string $staging) use ($publication, $service) {
            $draft = $service->createDraft('feed.json', $staging);
            $service->append($draft, 'new', $publication);

            return [$publication => $service->finishDraft($draft)];
        }))
            ->toThrow(CloseFeedException::class, 'Unable to publish the feed ownership registry:')
            ->and(file_get_contents($publication))
            ->toBe('old')
            ->and($ownership)
            ->toBeFile();

        $service->publish($publication, function (string $staging) use ($publication, $service) {
            $draft = $service->createDraft('feed.json', $staging);
            $service->append($draft, 'after-rollback', $publication);

            return [$publication => $service->finishDraft($draft)];
        });

        expect(file_get_contents($publication))->toBe('after-rollback');
    } finally {
        $directory->delete();
    }
});

test('rejects an invalid ownership registry before moving publication files', function () {
    $directory   = (new TemporaryDirectory)->create();
    $publication = $directory->path('feed.json');
    $ownership   = $directory->path('.laravel-feeds' . DIRECTORY_SEPARATOR . 'ownership.json');
    $file        = new ControlledMoveFilesystem;
    $service     = new FilesystemService($file);

    if (! is_dir(dirname($ownership))) {
        mkdir(dirname($ownership));
    }
    file_put_contents($ownership, 'invalid');

    try {
        expect(fn () => $service->publish($publication, function (string $staging) use ($publication, $service) {
            $draft = $service->createDraft('feed.json', $staging);
            $service->append($draft, 'new', $publication);

            return [$publication => $service->finishDraft($draft)];
        }))
            ->toThrow(RuntimeException::class, 'Unable to read a valid feed ownership registry:')
            ->and($publication)
            ->not->toBeFile()
            ->and(file_get_contents($ownership))
            ->toBe('invalid')
            ->and($file->moves)
            ->toBe([])
            ->and(glob($directory->path() . DIRECTORY_SEPARATOR . '.feeds_staging_*'))
            ->toBe([]);
    } finally {
        $directory->delete();
    }
});
