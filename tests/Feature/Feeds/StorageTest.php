<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Enums\FeedFormatEnum;
use DragonCode\LaravelFeed\Events\FeedFinishedEvent;
use DragonCode\LaravelFeed\Events\FeedStartingEvent;
use DragonCode\LaravelFeed\Exceptions\FeedGenerationException;
use DragonCode\LaravelFeed\Exceptions\UnsupportedStorageDiskException;
use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Feeds\Info\FeedInfo;
use DragonCode\LaravelFeed\Services\FilesystemService;
use DragonCode\LaravelFeed\Services\GeneratorService;
use Illuminate\Contracts\Filesystem\Filesystem as FilesystemContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Config;
use League\Flysystem\DecoratedAdapter;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use Workbench\App\Data\NewsFakeData;
use Workbench\App\Models\News;

final class RemoteFeedFilesystemAdapter extends DecoratedAdapter
{
    public array $moves = [];

    public int $streamWrites = 0;

    protected ?string $failedDestination = null;

    protected ?string $failedDelete = null;

    protected bool $failedDirectoryDelete = false;

    public function failNextDelete(string $path): void
    {
        $this->failedDelete = $path;
    }

    public function failNextDirectoryDelete(): void
    {
        $this->failedDirectoryDelete = true;
    }

    public function failNextMoveTo(string $destination): void
    {
        $this->failedDestination = $destination;
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $this->streamWrites++;

        parent::writeStream($path, $contents, $config);
    }

    public function delete(string $path): void
    {
        if ($this->failedDelete === $path) {
            $this->failedDelete = null;

            throw UnableToDeleteFile::atLocation($path, 'Injected remote delete failure.');
        }

        parent::delete($path);
    }

    public function deleteDirectory(string $path): void
    {
        if ($this->failedDirectoryDelete) {
            $this->failedDirectoryDelete = false;

            throw UnableToDeleteDirectory::atLocation($path, 'Injected remote directory delete failure.');
        }

        parent::deleteDirectory($path);
    }

    public function move(string $source, string $destination, Config $config): void
    {
        $this->moves[] = [$source, $destination];

        if ($this->failedDestination === $destination) {
            $this->failedDestination = null;

            throw UnableToMoveFile::because('Injected remote move failure.', $source, $destination);
        }

        parent::move($source, $destination, $config);
    }
}

final class RemoteFeedStorage
{
    public static RemoteFeedFilesystemAdapter $adapter;

    public static TemporaryDirectory $directory;

    public static function create(): void
    {
        self::$directory = (new TemporaryDirectory)->create();
        self::$adapter   = new RemoteFeedFilesystemAdapter(
            new LocalFilesystemAdapter(self::$directory->path())
        );

        Storage::set('remote-feeds', new FilesystemAdapter(
            new Flysystem(self::$adapter),
            self::$adapter,
            ['root' => 'remote-root']
        ));
    }

    public static function delete(): void
    {
        Storage::forgetDisk('remote-feeds');

        self::$directory->delete();
    }
}

final class LocalStorageFeed extends Feed
{
    protected FeedFormatEnum $format = FeedFormatEnum::JsonLines;

    protected string $storage = 'local-feeds';

    public function builder(): Builder
    {
        return News::query();
    }

    public function filename(): string
    {
        return 'exports/local-feed.jsonl';
    }

    public function info(): FeedInfo
    {
        return new FeedInfo;
    }
}

final class RemoteStorageFeed extends Feed
{
    public static int $perFile = 0;

    protected FeedFormatEnum $format = FeedFormatEnum::JsonLines;

    protected string $storage = 'remote-feeds';

    public function builder(): Builder
    {
        return News::query();
    }

    public function filename(): string
    {
        return 'exports/remote-feed.jsonl';
    }

    public function info(): FeedInfo
    {
        return new FeedInfo;
    }

    public function perFile(): int
    {
        return self::$perFile;
    }
}

final class UnsupportedStorageFeed extends Feed
{
    public static int $builderCalls = 0;

    protected string $storage = 'unsupported-feeds';

    public function builder(): Builder
    {
        self::$builderCalls++;

        return News::query();
    }
}

function remoteStagingFiles(FilesystemAdapter $storage): array
{
    return array_values(array_filter(
        $storage->allFiles(),
        static fn (string $path) => str_contains($path, '.feeds_staging_')
    ));
}

function remoteFeedDraft(string $staging, string $name): string
{
    $path = $staging . DIRECTORY_SEPARATOR . $name;

    file_put_contents($path, $name);

    return $path;
}

beforeEach(function () {
    RemoteStorageFeed::$perFile           = 0;
    UnsupportedStorageFeed::$builderCalls = 0;

    Storage::fake('local-feeds');
    RemoteFeedStorage::create();
});

afterEach(function () {
    Storage::forgetDisk('local-feeds');
    Storage::forgetDisk('unsupported-feeds');

    RemoteFeedStorage::delete();
});

test('keeps local disk generation and absolute paths supported', function () {
    createNews(...NewsFakeData::toArray());

    $feed   = app(LocalStorageFeed::class);
    $result = app(GeneratorService::class)->feed($feed);

    expect($feed->storagePath())
        ->toBe('exports/local-feed.jsonl')
        ->and($feed->path())
        ->toBeFile()
        ->and($feed->storage()->exists($feed->storagePath()))
        ->toBeTrue()
        ->and($result->paths)
        ->toBe([$feed->path()]);
});

test('publishes a feed to a remote Flysystem adapter through streams', function () {
    createNews(...NewsFakeData::toArray());

    $feed    = app(RemoteStorageFeed::class);
    $storage = $feed->storage();
    $result  = app(GeneratorService::class)->feed($feed);

    expect($feed->storagePath())
        ->toBe('exports/remote-feed.jsonl')
        ->and($feed->path())
        ->toBe($storage->path($feed->storagePath()))
        ->and($storage->exists($feed->storagePath()))
        ->toBeTrue()
        ->and($storage->get($feed->storagePath()))
        ->toContain('Some content 1')
        ->and(RemoteFeedStorage::$adapter->streamWrites)
        ->toBe(1)
        ->and(remoteStagingFiles($storage))
        ->toBe([])
        ->and($result->paths)
        ->toBe([$feed->path()]);
});

test('restores remote feed parts when publication fails', function () {
    RemoteStorageFeed::$perFile = 1;

    createNews(...NewsFakeData::toArray());

    $feed      = app(RemoteStorageFeed::class);
    $storage   = $feed->storage();
    $generator = app(GeneratorService::class);
    $result    = $generator->feed($feed);
    $published = [];

    foreach (array_keys($result->paths) as $offset) {
        $path             = $feed->storagePath($offset + 1);
        $published[$path] = 'old-' . ($offset + 1);

        $storage->put($path, $published[$path]);
    }

    Event::fake();

    RemoteFeedStorage::$adapter->failNextMoveTo($feed->storagePath(2));

    expect(fn () => $generator->feed($feed))
        ->toThrow(FeedGenerationException::class, 'Injected remote move failure.');

    foreach ($published as $path => $content) {
        expect($storage->get($path))->toBe($content);
    }

    expect(remoteStagingFiles($storage))->toBe([]);

    $ownership = dirname($feed->storagePath()) . '/.laravel-feeds/ownership.json';

    RemoteFeedStorage::$adapter->failNextMoveTo($ownership);

    expect(fn () => $generator->feed($feed))
        ->toThrow(FeedGenerationException::class, $ownership);

    foreach ($published as $path => $content) {
        expect($storage->get($path))->toBe($content);
    }

    expect($storage->exists($ownership))
        ->toBeTrue()
        ->and(remoteStagingFiles($storage))
        ->toBe([]);

    Event::assertNotDispatched(FeedFinishedEvent::class);
});

test('replaces remote feeds and removes obsolete split parts', function () {
    RemoteStorageFeed::$perFile = 1;

    createNews(...NewsFakeData::toArray());

    $feed      = app(RemoteStorageFeed::class);
    $storage   = $feed->storage();
    $generator = app(GeneratorService::class);

    $generator->feed($feed);

    RemoteStorageFeed::$perFile = 0;

    $generator->feed($feed);

    expect($storage->exists($feed->storagePath()))
        ->toBeTrue()
        ->and($storage->exists($feed->storagePath(1)))
        ->toBeFalse()
        ->and($storage->exists($feed->storagePath(2)))
        ->toBeFalse()
        ->and($storage->exists($feed->storagePath(3)))
        ->toBeFalse();

    $storage->put($feed->storagePath(), 'old-single');

    $generator->feed($feed);

    expect($storage->get($feed->storagePath()))
        ->not->toBe('old-single')
        ->and(remoteStagingFiles($storage))
        ->toBe([]);

    $independent = $feed->storagePath(1);

    $storage->put($independent, 'independent');

    $generator->feed($feed);

    $published = $storage->get($feed->storagePath());
    $moveCount = count(RemoteFeedStorage::$adapter->moves);

    expect($storage->get($independent))->toBe('independent');

    RemoteStorageFeed::$perFile = 1;

    expect(fn () => $generator->feed($feed))
        ->toThrow(
            FeedGenerationException::class,
            "Feed publication target is not owned: [$independent]."
        );

    expect($storage->get($feed->storagePath()))
        ->toBe($published)
        ->and($storage->get($independent))
        ->toBe('independent')
        ->and(RemoteFeedStorage::$adapter->moves)
        ->toHaveCount($moveCount)
        ->and(remoteStagingFiles($storage))
        ->toBe([]);
});

test('removes a pre-registry remote primary file when publishing split parts', function () {
    $storage     = app(RemoteStorageFeed::class)->storage();
    $filesystem  = app(FilesystemService::class);
    $publication = 'exports/legacy.jsonl';
    $first       = 'exports/legacy-1.jsonl';
    $second      = 'exports/legacy-2.jsonl';

    $storage->put($publication, 'legacy-primary');

    $filesystem->publishTo($storage, $publication, static fn (string $staging) => [
        $first  => remoteFeedDraft($staging, 'first'),
        $second => remoteFeedDraft($staging, 'second'),
    ]);

    expect($storage->exists($publication))
        ->toBeFalse()
        ->and($storage->get($first))
        ->toBe('first')
        ->and($storage->get($second))
        ->toBe('second')
        ->and(remoteStagingFiles($storage))
        ->toBe([]);
});

test('validates remote publication drafts', function () {
    $storage     = app(RemoteStorageFeed::class)->storage();
    $filesystem  = app(FilesystemService::class);
    $publication = 'exports/validation.jsonl';
    $split       = 'exports/validation-1.jsonl';

    $cases = [
        'array of staged files' => [
            static fn (string $staging) => null,
            'The publication callback must return an array of staged files.',
        ],
        'non-empty staged files' => [
            static fn (string $staging) => [],
            'No staged feeds were provided for publication',
        ],
        'string paths' => [
            static fn (string $staging) => [remoteFeedDraft($staging, 'numeric-target')],
            'Staged feed paths and publication targets must be strings.',
        ],
        'publication target' => [
            static fn (string $staging) => [
                'other/validation.jsonl' => remoteFeedDraft($staging, 'invalid-target'),
            ],
            'Invalid feed publication target',
        ],
        'existing draft' => [
            static fn (string $staging) => [$publication => $staging . DIRECTORY_SEPARATOR . 'missing'],
            'Staged feed does not exist',
        ],
        'unique target' => [
            static fn (string $staging) => [
                $publication                         => remoteFeedDraft($staging, 'first-target'),
                str_replace('/', '\\', $publication) => remoteFeedDraft($staging, 'second-target'),
            ],
            'Duplicate feed publication target',
        ],
        'unique draft' => [
            static function (string $staging) use ($publication, $split) {
                $draft = remoteFeedDraft($staging, 'duplicate-draft');

                return [
                    $publication => $draft,
                    $split       => $draft,
                ];
            },
            'Duplicate staged feed',
        ],
    ];

    foreach ($cases as [$drafts, $message]) {
        expect(fn () => $filesystem->publishTo($storage, $publication, $drafts))
            ->toThrow(RuntimeException::class, $message);
    }
});

test('reports remote staging cleanup failures after a successful publication', function () {
    createNews(...NewsFakeData::toArray());

    $feed = app(RemoteStorageFeed::class);

    RemoteFeedStorage::$adapter->failNextDirectoryDelete();

    expect(fn () => app(GeneratorService::class)->feed($feed))
        ->toThrow(FeedGenerationException::class, 'Unable to clean the remote feed staging directory');

    expect($feed->storage()->exists($feed->storagePath()))->toBeTrue();
});

test('reports publication and remote staging cleanup failures together', function () {
    createNews(...NewsFakeData::toArray());

    $feed = app(RemoteStorageFeed::class);

    RemoteFeedStorage::$adapter->failNextMoveTo($feed->storagePath());
    RemoteFeedStorage::$adapter->failNextDirectoryDelete();

    expect(fn () => app(GeneratorService::class)->feed($feed))
        ->toThrow(FeedGenerationException::class, 'Unable to clean the remote feed staging directory')
        ->and($feed->storage()->exists($feed->storagePath()))
        ->toBeFalse();
});

test('preserves remote staging when rollback fails', function () {
    RemoteStorageFeed::$perFile = 1;

    createNews(...NewsFakeData::toArray());

    $feed      = app(RemoteStorageFeed::class);
    $storage   = $feed->storage();
    $generator = app(GeneratorService::class);
    $result    = $generator->feed($feed);
    $published = [];

    foreach (array_keys($result->paths) as $offset) {
        $path             = $feed->storagePath($offset + 1);
        $published[$path] = 'old-' . ($offset + 1);

        $storage->put($path, $published[$path]);
    }

    RemoteFeedStorage::$adapter->failNextMoveTo($feed->storagePath(2));
    RemoteFeedStorage::$adapter->failNextDelete($feed->storagePath(1));

    expect(fn () => $generator->feed($feed))
        ->toThrow(FeedGenerationException::class, 'Rollback failed:');

    foreach ($published as $path => $content) {
        expect($storage->get($path))->toBe($content);
    }

    expect(remoteStagingFiles($storage))->not->toBe([]);
});

test('rejects unsupported storage contracts before building the database query', function () {
    Event::fake();

    Storage::set('unsupported-feeds', mock(FilesystemContract::class));

    expect(fn () => app(GeneratorService::class)->feed(app(UnsupportedStorageFeed::class)))
        ->toThrow(
            UnsupportedStorageDiskException::class,
            'Feed storage disk [unsupported-feeds] must resolve to [Illuminate\\Filesystem\\FilesystemAdapter]'
        )
        ->and(UnsupportedStorageFeed::$builderCalls)
        ->toBe(0);

    Event::assertNotDispatched(FeedStartingEvent::class);
});
