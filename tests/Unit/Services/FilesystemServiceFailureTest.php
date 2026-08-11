<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Exceptions\CloseFeedException;
use DragonCode\LaravelFeed\Exceptions\OpenFeedException;
use DragonCode\LaravelFeed\Services\FilesystemService;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemAdapter;
use League\Flysystem\Config;
use League\Flysystem\DecoratedAdapter;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use Spatie\TemporaryDirectory\TemporaryDirectory;

final class FailureModeFilesystem extends Filesystem
{
    public array $falseDirectories = [];

    public array $falseDirectoryBasenames = [];

    public array $failMoveSources = [];

    public array $failMoveTargets = [];

    public array $failDeletes = [];

    public array $throwEnsureDirectories = [];

    public array $throwExistsBasenames = [];

    public array $failBackupRestoresTo = [];

    public bool $failAllMakeDirectories = false;

    public function exists($path)
    {
        if (in_array(basename($path), $this->throwExistsBasenames, true)) {
            throw new RuntimeException("Injected exists failure: [$path].");
        }

        return parent::exists($path);
    }

    public function ensureDirectoryExists($path, $mode = 0o755, $recursive = true)
    {
        if (in_array($path, $this->throwEnsureDirectories, true)) {
            throw new RuntimeException("Injected directory failure: [$path].");
        }

        parent::ensureDirectoryExists($path, $mode, $recursive);
    }

    public function makeDirectory($path, $mode = 0o755, $recursive = false, $force = false)
    {
        if ($this->failAllMakeDirectories) {
            return false;
        }

        return parent::makeDirectory($path, $mode, $recursive, $force);
    }

    public function isDirectory($directory)
    {
        if (
            in_array($directory, $this->falseDirectories, true)
            || in_array(basename($directory), $this->falseDirectoryBasenames, true)
        ) {
            return false;
        }

        return parent::isDirectory($directory);
    }

    public function move($path, $target)
    {
        if ($this->consume($this->failMoveSources, $path) || $this->consume($this->failMoveTargets, $target)) {
            return false;
        }

        if (
            isset($this->failBackupRestoresTo[$target])
            && basename(dirname($path)) === 'backups'
        ) {
            unset($this->failBackupRestoresTo[$target]);

            return false;
        }

        return parent::move($path, $target);
    }

    public function delete($paths)
    {
        if (is_string($paths) && $this->consume($this->failDeletes, $paths)) {
            return false;
        }

        return parent::delete($paths);
    }

    private function consume(array &$failures, string $path): bool
    {
        if (($failures[$path] ?? 0) < 1) {
            return false;
        }

        $failures[$path]--;

        return true;
    }
}

final class FailedDeletionTemporaryDirectory extends TemporaryDirectory
{
    public function delete(): bool
    {
        return false;
    }
}

final class FailureModeFilesystemService extends FilesystemService
{
    public ?string $controlledDraftPath = null;

    public ?TemporaryDirectory $controlledStaging = null;

    public bool $useControlledTemporaryDirectory = false;

    public bool $failOwnershipWrite = false;

    public bool $deleteStorageDraftsAfterValidation = false;

    protected function createStagingDirectory(string $path): TemporaryDirectory
    {
        return $this->controlledStaging ?? parent::createStagingDirectory($path);
    }

    protected function createTemporaryDirectory(string $location, Closure $name): TemporaryDirectory
    {
        if ($this->useControlledTemporaryDirectory && $this->controlledStaging !== null) {
            return $this->controlledStaging;
        }

        return parent::createTemporaryDirectory($location, $name);
    }

    protected function draftPath(string $filename, ?string $directory = null): string
    {
        if ($this->controlledDraftPath !== null) {
            return $this->controlledDraftPath;
        }

        return parent::draftPath($filename, $directory);
    }

    public function append($resource, string $content, string $path): void
    {
        if ($this->failOwnershipWrite && basename($path) === 'ownership.json') {
            throw new RuntimeException('Injected ownership write failure.');
        }

        parent::append($resource, $content, $path);
    }

    public function decodeOwnershipWithCollapsedPaths(string $contents): array
    {
        return $this->decodeOwnership(
            $contents,
            static fn (string $filename) => $filename,
            static fn (string $path) => 'same-path',
        );
    }

    public function replaceCollidingOwnership(): array
    {
        return $this->nextPublicationOwnership(
            'feed.json',
            ['alias.json'],
            ['old.json' => 'other.json'],
            static fn (string $filename) => $filename,
            static fn (string $path) => match ($path) {
                'alias.json', 'old.json' => 'collision',
                default                  => $path,
            },
        );
    }

    protected function validateStorageDrafts(string $path, mixed $drafts): array
    {
        $validated = parent::validateStorageDrafts($path, $drafts);

        if ($this->deleteStorageDraftsAfterValidation) {
            foreach ($validated as $draft) {
                $this->file->delete($draft);
            }
        }

        return $validated;
    }
}

final class FailureRemoteAdapter extends DecoratedAdapter
{
    public ?string $failedDestination = null;

    public ?string $failedDelete = null;

    public bool $dropBackups = false;

    public function move(string $source, string $destination, Config $config): void
    {
        if ($this->failedDestination === $destination) {
            $this->failedDestination = null;

            throw UnableToMoveFile::because('Injected remote move failure.', $source, $destination);
        }

        parent::move($source, $destination, $config);

        if ($this->dropBackups && str_contains($destination, '/backups/')) {
            parent::delete($destination);
        }
    }

    public function delete(string $path): void
    {
        if ($this->failedDelete === $path) {
            $this->failedDelete = null;

            throw UnableToDeleteFile::atLocation($path, 'Injected remote delete failure.');
        }

        parent::delete($path);
    }
}

function failureLocalStorage(TemporaryDirectory $directory): FilesystemAdapter
{
    $adapter = new LocalFilesystemAdapter($directory->path());

    return new FilesystemAdapter(
        new Flysystem($adapter),
        $adapter,
        ['root' => $directory->path()]
    );
}

function failureRemoteStorage(TemporaryDirectory $directory): array
{
    $adapter = new FailureRemoteAdapter(
        new LocalFilesystemAdapter($directory->path())
    );

    return [
        new FilesystemAdapter(
            new Flysystem($adapter),
            $adapter,
            ['root' => 'remote-root']
        ),
        $adapter,
    ];
}

function failureDraft(FilesystemService $service, string $staging, string $target, string $content = 'new'): string
{
    $draft = $service->createDraft(basename($target), $staging);

    $service->append($draft, $content, $target);

    return $service->finishDraft($draft);
}

function failureLocalService(FailureModeFilesystem $file, TemporaryDirectory $directory): FailureModeFilesystemService
{
    $service                    = new FailureModeFilesystemService($file);
    $service->controlledStaging = (new TemporaryDirectory)
        ->location($directory->path())
        ->name('staging')
        ->create();

    return $service;
}

function failureOwnershipPath(string $publication): string
{
    return dirname($publication) . DIRECTORY_SEPARATOR . '.laravel-feeds' . DIRECTORY_SEPARATOR . 'ownership.json';
}

function failureOwnershipContents(array $owners, string $format = 'dragon-code/laravel-feeds-ownership'): string
{
    return json_encode([
        'format'  => $format,
        'owners'  => $owners,
        'version' => 1,
    ], JSON_THROW_ON_ERROR);
}

test('cleans an allocated draft directory when collision detection fails', function () {
    $directory = (new TemporaryDirectory)->create();
    $draftDir  = $directory->path('draft');
    $draftPath = $draftDir . DIRECTORY_SEPARATOR . 'feeds_draft_file';

    file_put_contents($draftPath, 'collision');

    $file                       = new FailureModeFilesystem;
    $file->throwExistsBasenames = ['feeds_draft_file'];

    $service                      = new FailureModeFilesystemService($file);
    $service->controlledDraftPath = $draftPath;

    try {
        expect(fn () => $service->createDraft('feed.json', $directory->path()))
            ->toThrow(OpenFeedException::class, 'Injected exists failure')
            ->and($draftDir)
            ->not->toBeDirectory();
    } finally {
        $directory->delete();
    }
});

test('validates local storage callback results before publication', function () {
    $directory = (new TemporaryDirectory)->create();
    $service   = new FilesystemService(new Filesystem);
    $storage   = failureLocalStorage($directory);

    try {
        expect(fn () => $service->publishTo($storage, 'feed.json', static fn () => null))
            ->toThrow(RuntimeException::class, 'The publication callback must return an array');

        expect(fn () => $service->publishTo($storage, 'feed.json', static fn () => ['draft']))
            ->toThrow(RuntimeException::class, 'Staged feed paths and publication targets must be strings');
    } finally {
        $directory->delete();
    }
});

test('reports a local staging cleanup failure after a successful publication', function () {
    $directory = (new TemporaryDirectory)->create();
    $staging   = (new FailedDeletionTemporaryDirectory)
        ->location($directory->path())
        ->name('staging')
        ->create();
    $publication                = $directory->path('feed.json');
    $service                    = new FailureModeFilesystemService(new Filesystem);
    $service->controlledStaging = $staging;

    try {
        expect(fn () => $service->publish($publication, fn (string $path) => [
            $publication => failureDraft($service, $path, $publication),
        ]))
            ->toThrow(RuntimeException::class, 'Unable to clean the feed staging directory')
            ->and(file_get_contents($publication))
            ->toBe('new');
    } finally {
        $directory->delete();
    }
});

test('combines publication and local staging cleanup failures', function () {
    $directory = (new TemporaryDirectory)->create();
    $staging   = (new FailedDeletionTemporaryDirectory)
        ->location($directory->path())
        ->name('staging')
        ->create();
    $service                    = new FailureModeFilesystemService(new Filesystem);
    $service->controlledStaging = $staging;

    try {
        expect(fn () => $service->publish(
            $directory->path('feed.json'),
            static fn () => throw new RuntimeException('Injected publication failure.')
        ))->toThrow(
            RuntimeException::class,
            'Injected publication failure. Unable to clean the feed staging directory'
        );
    } finally {
        $directory->delete();
    }
});

test('reports a remote local-staging cleanup failure', function () {
    $directory = (new TemporaryDirectory)->create();
    $staging   = (new FailedDeletionTemporaryDirectory)
        ->location($directory->path())
        ->name('local-staging')
        ->create();
    [$storage] = failureRemoteStorage($directory);

    $service                                  = new FailureModeFilesystemService(new Filesystem);
    $service->controlledStaging               = $staging;
    $service->useControlledTemporaryDirectory = true;

    try {
        expect(fn () => $service->publishTo(
            $storage,
            'feed.json',
            static fn () => throw new RuntimeException('Injected remote publication failure.')
        ))->toThrow(
            RuntimeException::class,
            'Injected remote publication failure. Unable to clean the local feed staging directory'
        );
    } finally {
        $directory->delete();
    }
});

test('wraps staging directory creation failures', function () {
    $directory                    = (new TemporaryDirectory)->create();
    $publication                  = $directory->path('feed.json');
    $file                         = new FailureModeFilesystem;
    $file->throwEnsureDirectories = [$directory->path()];

    try {
        expect(fn () => (new FilesystemService($file))->publish($publication, static fn () => []))
            ->toThrow(OpenFeedException::class, 'Injected directory failure');
    } finally {
        $directory->delete();
    }
});

test('wraps non-collision temporary directory failures', function () {
    $directory                    = (new TemporaryDirectory)->create();
    $file                         = new FailureModeFilesystem;
    $file->failAllMakeDirectories = true;

    try {
        expect(fn () => (new FilesystemService($file))->createDraft('feed.json', $directory->path()))
            ->toThrow(OpenFeedException::class, 'Unable to create the temporary directory');
    } finally {
        $directory->delete();
    }
});

test('rejects local publication targets from another directory', function () {
    $directory   = (new TemporaryDirectory)->create();
    $publication = $directory->path('feed.json');
    $target      = $directory->path('other' . DIRECTORY_SEPARATOR . 'feed-1.json');
    $service     = new FilesystemService(new Filesystem);

    try {
        expect(fn () => $service->publish($publication, fn (string $staging) => [
            $target => failureDraft($service, $staging, $target),
        ]))->toThrow(RuntimeException::class, 'Invalid feed publication target');
    } finally {
        $directory->delete();
    }
});

test('rejects a backup path that cannot become a directory', function () {
    $directory                     = (new TemporaryDirectory)->create();
    $publication                   = $directory->path('feed.json');
    $file                          = new FailureModeFilesystem;
    $file->falseDirectoryBasenames = ['backups'];
    $service                       = new FilesystemService($file);

    file_put_contents($publication, 'old');

    try {
        expect(fn () => $service->publish($publication, fn (string $staging) => [
            $publication => failureDraft($service, $staging, $publication),
        ]))
            ->toThrow(CloseFeedException::class, 'Unable to create the feed backup directory')
            ->and(file_get_contents($publication))
            ->toBe('old');
    } finally {
        $directory->delete();
    }
});

test('reports a failure to move the published feed into backup', function () {
    $directory                           = (new TemporaryDirectory)->create();
    $publication                         = $directory->path('feed.json');
    $file                                = new FailureModeFilesystem;
    $file->failMoveSources[$publication] = 1;
    $service                             = new FilesystemService($file);

    file_put_contents($publication, 'old');

    try {
        expect(fn () => $service->publish($publication, fn (string $staging) => [
            $publication => failureDraft($service, $staging, $publication),
        ]))
            ->toThrow(CloseFeedException::class, 'Unable to back up the published feed')
            ->and(file_get_contents($publication))
            ->toBe('old');
    } finally {
        $directory->delete();
    }
});

test('rolls back when the ownership directory cannot be created', function () {
    $directory              = (new TemporaryDirectory)->create();
    $publication            = $directory->path('feed.json');
    $file                   = new FailureModeFilesystem;
    $file->falseDirectories = [dirname(failureOwnershipPath($publication))];
    $service                = new FilesystemService($file);

    try {
        expect(fn () => $service->publish($publication, fn (string $staging) => [
            $publication => failureDraft($service, $staging, $publication),
        ]))
            ->toThrow(CloseFeedException::class, 'Unable to create the feed ownership directory')
            ->and($publication)
            ->not->toBeFile();
    } finally {
        $directory->delete();
    }
});

test('closes the ownership draft when writing its contents fails', function () {
    $directory                   = (new TemporaryDirectory)->create();
    $publication                 = $directory->path('feed.json');
    $service                     = new FailureModeFilesystemService(new Filesystem);
    $service->failOwnershipWrite = true;

    try {
        expect(fn () => $service->publish($publication, fn (string $staging) => [
            $publication => failureDraft($service, $staging, $publication),
        ]))
            ->toThrow(RuntimeException::class, 'Injected ownership write failure')
            ->and($publication)
            ->not->toBeFile();
    } finally {
        $directory->delete();
    }
});

test('rejects a local ownership registry directory', function () {
    $directory   = (new TemporaryDirectory)->create();
    $publication = $directory->path('feed.json');
    $ownership   = failureOwnershipPath($publication);
    $service     = new FilesystemService(new Filesystem);

    mkdir($ownership, recursive: true);

    try {
        expect(fn () => $service->publish($publication, fn (string $staging) => [
            $publication => failureDraft($service, $staging, $publication),
        ]))->toThrow(RuntimeException::class, 'Unable to read a valid feed ownership registry');
    } finally {
        $directory->delete();
    }
});

test('rejects unsupported and invalid local ownership entries', function () {
    $directory   = (new TemporaryDirectory)->create();
    $publication = $directory->path('feed.json');
    $ownership   = failureOwnershipPath($publication);
    $service     = new FilesystemService(new Filesystem);

    mkdir(dirname($ownership), recursive: true);

    $contents = [
        failureOwnershipContents([], 'unsupported'),
        failureOwnershipContents(['../feed.json' => 'feed.json']),
    ];

    try {
        foreach ($contents as $content) {
            file_put_contents($ownership, $content);

            expect(fn () => $service->publish($publication, fn (string $staging) => [
                $publication => failureDraft($service, $staging, $publication),
            ]))->toThrow(RuntimeException::class, 'Unable to read a valid feed ownership registry');
        }
    } finally {
        $directory->delete();
    }
});

test('rejects ownership entries that resolve to the same path', function () {
    $service = new FailureModeFilesystemService(new Filesystem);
    $content = failureOwnershipContents([
        'feed.json'   => 'feed.json',
        'feed-1.json' => 'feed.json',
    ]);

    expect(fn () => $service->decodeOwnershipWithCollapsedPaths($content))
        ->toThrow(RuntimeException::class, 'Duplicate feed ownership entry');
});

test('replaces a colliding ownership entry before assigning the target', function () {
    expect((new FailureModeFilesystemService(new Filesystem))->replaceCollidingOwnership())
        ->toBe(['alias.json' => 'feed.json']);
});

test('preserves local staging when rollback cannot remove an installed feed', function () {
    $directory                      = (new TemporaryDirectory)->create();
    $publication                    = $directory->path('feed.json');
    $first                          = $directory->path('feed-1.json');
    $second                         = $directory->path('feed-2.json');
    $file                           = new FailureModeFilesystem;
    $file->failMoveTargets[$second] = 1;
    $file->failDeletes[$first]      = 1;
    $service                        = failureLocalService($file, $directory);

    try {
        expect(fn () => $service->publish($publication, fn (string $staging) => [
            $first  => failureDraft($service, $staging, $first, 'first'),
            $second => failureDraft($service, $staging, $second, 'second'),
        ]))
            ->toThrow(CloseFeedException::class, 'Rollback failed:')
            ->and(file_get_contents($first))
            ->toBe('first');
    } finally {
        $directory->delete();
    }
});

test('reports a rollback failure when the replaced feed cannot be cleared', function () {
    $directory                         = (new TemporaryDirectory)->create();
    $publication                       = $directory->path('feed.json');
    $ownership                         = failureOwnershipPath($publication);
    $file                              = new FailureModeFilesystem;
    $file->failMoveTargets[$ownership] = 1;
    $file->failDeletes[$publication]   = 2;
    $service                           = failureLocalService($file, $directory);

    file_put_contents($publication, 'old');

    try {
        expect(fn () => $service->publish($publication, fn (string $staging) => [
            $publication => failureDraft($service, $staging, $publication),
        ]))
            ->toThrow(CloseFeedException::class, 'Unable to clear the feed path during rollback')
            ->and(file_get_contents($publication))
            ->toBe('new');
    } finally {
        $directory->delete();
    }
});

test('reports a rollback failure when a backup cannot be restored', function () {
    $directory                                = (new TemporaryDirectory)->create();
    $publication                              = $directory->path('feed.json');
    $ownership                                = failureOwnershipPath($publication);
    $file                                     = new FailureModeFilesystem;
    $file->failMoveTargets[$ownership]        = 1;
    $file->failBackupRestoresTo[$publication] = true;
    $service                                  = failureLocalService($file, $directory);

    file_put_contents($publication, 'old');

    try {
        expect(fn () => $service->publish($publication, fn (string $staging) => [
            $publication => failureDraft($service, $staging, $publication),
        ]))
            ->toThrow(CloseFeedException::class, 'Unable to restore the published feed during rollback')
            ->and($publication)
            ->not->toBeFile();
    } finally {
        $directory->delete();
    }
});

test('rejects remote ownership registries represented by directories', function () {
    $directory = (new TemporaryDirectory)->create();
    [$storage] = failureRemoteStorage($directory);
    $service   = new FilesystemService(new Filesystem);
    $ownership = '.laravel-feeds/ownership.json';

    $storage->makeDirectory($ownership);

    try {
        expect(fn () => $service->publishTo($storage, 'feed.json', fn (string $staging) => [
            'feed.json' => failureDraft($service, $staging, 'feed.json'),
        ]))->toThrow(RuntimeException::class, 'Unable to read a valid feed ownership registry');
    } finally {
        $directory->delete();
    }
});

test('wraps invalid remote ownership registry contents', function () {
    $directory = (new TemporaryDirectory)->create();
    [$storage] = failureRemoteStorage($directory);
    $service   = new FilesystemService(new Filesystem);
    $ownership = '.laravel-feeds/ownership.json';

    $storage->put($ownership, 'invalid');

    try {
        expect(fn () => $service->publishTo($storage, 'feed.json', fn (string $staging) => [
            'feed.json' => failureDraft($service, $staging, 'feed.json'),
        ]))->toThrow(RuntimeException::class, 'Unable to read a valid feed ownership registry');
    } finally {
        $directory->delete();
    }
});

test('reports missing remote backups and keeps targets that could not be removed', function () {
    $directory           = (new TemporaryDirectory)->create();
    [$storage, $adapter] = failureRemoteStorage($directory);
    $service             = new FilesystemService(new Filesystem);
    $first               = 'feed-1.json';
    $second              = 'feed-2.json';

    try {
        $service->publishTo($storage, 'feed.json', fn (string $staging) => [
            $first  => failureDraft($service, $staging, $first, 'old-first'),
            $second => failureDraft($service, $staging, $second, 'old-second'),
        ]);

        $adapter->dropBackups       = true;
        $adapter->failedDestination = $second;
        $adapter->failedDelete      = $first;

        expect(fn () => $service->publishTo($storage, 'feed.json', fn (string $staging) => [
            $first  => failureDraft($service, $staging, $first, 'new-first'),
            $second => failureDraft($service, $staging, $second, 'new-second'),
        ]))
            ->toThrow(CloseFeedException::class, 'Feed backup is missing during rollback')
            ->and($storage->get($first))
            ->toBe('new-first');
    } finally {
        $directory->delete();
    }
});

test('reports a remote draft that disappears after validation', function () {
    $directory                                   = (new TemporaryDirectory)->create();
    [$storage]                                   = failureRemoteStorage($directory);
    $service                                     = new FailureModeFilesystemService(new Filesystem);
    $service->deleteStorageDraftsAfterValidation = true;

    try {
        expect(fn () => $service->publishTo($storage, 'feed.json', fn (string $staging) => [
            'feed.json' => failureDraft($service, $staging, 'feed.json'),
        ]))->toThrow(CloseFeedException::class, 'Unable to open the staged feed for reading');
    } finally {
        $directory->delete();
    }
});
