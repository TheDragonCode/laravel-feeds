<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Services;

use Closure;
use DragonCode\LaravelFeed\Exceptions\CloseFeedException;
use DragonCode\LaravelFeed\Exceptions\OpenFeedException;
use DragonCode\LaravelFeed\Exceptions\ResourceMetaException;
use DragonCode\LaravelFeed\Exceptions\WriteFeedException;
use Illuminate\Filesystem\Filesystem as File;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Filesystem\LockableFile;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Local\LocalFilesystemAdapter;
use RuntimeException;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use Throwable;

use function array_keys;
use function array_reverse;
use function basename;
use function bin2hex;
use function dirname;
use function fclose;
use function fopen;
use function fwrite;
use function get_class;
use function hash;
use function implode;
use function is_array;
use function is_resource;
use function is_string;
use function pathinfo;
use function preg_match;
use function preg_quote;
use function random_bytes;
use function realpath;
use function sort;
use function storage_path;
use function str_replace;
use function stream_get_meta_data;
use function strlen;
use function strtolower;
use function substr;
use function sys_get_temp_dir;

class FilesystemService
{
    protected const MAX_PATH_ATTEMPTS = 10;

    public function __construct(
        protected File $file,
    ) {}

    /** @return resource */
    public function createDraft(string $filename, ?string $directory = null) // @pest-ignore-type
    {
        $temp    = $filename;
        $cleanup = false;

        try {
            for ($attempt = 0; $attempt < self::MAX_PATH_ATTEMPTS; $attempt++) {
                $temp     = $this->draftPath($filename, $directory);
                $cleanup  = true;
                $resource = @fopen($temp, 'xb');

                if ($resource !== false) {
                    return $resource;
                }

                $collision = $this->file->exists($temp);

                $this->cleanTemporaryDirectory($temp);

                $cleanup = false;

                if (! $collision) {
                    throw new RuntimeException('Unable to open resource for writing.');
                }
            }

            throw new RuntimeException(
                'Unable to create a unique feed draft after [' . self::MAX_PATH_ATTEMPTS . '] attempts.'
            );
        } catch (Throwable $e) {
            if ($cleanup) {
                $this->cleanTemporaryDirectory($temp);
            }

            throw new OpenFeedException($temp, $e);
        }
    }

    /** @param  resource  $resource */
    public function append($resource, string $content, string $path): void // @pest-ignore-type
    {
        $expectedBytes = strlen($content);
        $writtenBytes  = 0;

        while ($writtenBytes < $expectedBytes) {
            $buffer = $writtenBytes === 0 ? $content : substr($content, $writtenBytes);
            $bytes  = fwrite($resource, $buffer);

            if ($bytes === false || $bytes === 0) {
                throw new WriteFeedException($path, $writtenBytes, $expectedBytes);
            }

            $writtenBytes += $bytes;
        }
    }

    /** @param  resource  $resource */
    public function release($resource, string $path): void // @pest-ignore-type
    {
        $temp = null;

        try {
            $temp = $this->finishDraft($resource);

            $this->publish($path, fn () => [$path => $temp]);
            // @codeCoverageIgnoreStart
        } catch (Throwable $e) {
            if ($e instanceof CloseFeedException) {
                throw $e;
            }

            throw new CloseFeedException($path, $e);
        } finally {
            if ($temp !== null) {
                $this->cleanTemporaryDirectory($temp);
            }
        }
        // @codeCoverageIgnoreEnd
    }

    public function finishDraft(mixed $resource): string
    {
        $temp = $this->getMetaPath($resource);

        $this->close($resource);

        return $temp;
    }

    public function lock(string $path, Closure $callback, bool $block = true): mixed
    {
        $lock = new LockableFile($this->lockPath($path), 'c+');

        try {
            $lock->getExclusiveLock($block);

            return $callback();
        } finally {
            $lock->close();
        }
    }

    public function publishTo(FilesystemAdapter $storage, string $path, Closure $callback): void
    {
        if ($storage->getAdapter() instanceof LocalFilesystemAdapter) {
            $this->publish($storage->path($path), function (string $staging) use ($callback, $storage) {
                $drafts = $callback($staging);

                if (! is_array($drafts)) {
                    return $drafts;
                }

                $resolved = [];

                foreach ($drafts as $target => $draft) {
                    if (! is_string($target)) {
                        throw new RuntimeException('Staged feed paths and publication targets must be strings.');
                    }

                    $resolved[$storage->path($target)] = $draft;
                }

                return $resolved;
            });

            return;
        }

        $this->publishRemote($storage, $path, $callback);
    }

    public function publish(string $path, Closure $callback): void
    {
        $this->lock($path, function () use ($callback, $path) {
            $staging = $this->createStagingDirectory($path);
            $cleanup = true;
            $failure = null;

            try {
                $drafts = $callback($staging->path());

                if (! is_array($drafts)) {
                    throw new RuntimeException('The publication callback must return an array of staged files.');
                }

                $this->commit($path, $drafts, $staging->path(), $cleanup);
            } catch (Throwable $e) {
                $failure = $e;
            }

            if ($cleanup && ! $staging->delete()) {
                $cleanupFailure = new RuntimeException("Unable to clean the feed staging directory: [{$staging->path()}].");

                if ($failure !== null) {
                    throw new RuntimeException(
                        $failure->getMessage() . ' ' . $cleanupFailure->getMessage(),
                        previous: $failure
                    );
                }

                throw $cleanupFailure;
            }

            if ($failure !== null) {
                throw $failure;
            }
        });
    }

    protected function publishRemote(FilesystemAdapter $storage, string $path, Closure $callback): void
    {
        $filesystem = $storage->getDriver();
        $lock       = get_class($storage->getAdapter()) . ':' . $storage->path($path);

        $this->lock($lock, function () use ($callback, $filesystem, $path) {
            $localStaging = $this->createTemporaryDirectory(
                sys_get_temp_dir(),
                fn () => 'laravel_feeds_' . $this->uniqueIdentifier()
            );
            $remoteStaging = $this->storageStagingPath($path);
            $cleanupRemote = false;
            $failure       = null;

            try {
                $drafts = $callback($localStaging->path());

                $this->commitStorage($filesystem, $path, $drafts, $remoteStaging, $cleanupRemote);
            } catch (Throwable $e) {
                $failure = $e;
            }

            if (! $localStaging->delete()) {
                $failure = $this->withCleanupFailure(
                    $failure,
                    new RuntimeException("Unable to clean the local feed staging directory: [{$localStaging->path()}].")
                );
            }

            if ($cleanupRemote) {
                try {
                    $filesystem->deleteDirectory($remoteStaging);
                } catch (Throwable $e) {
                    $failure = $this->withCleanupFailure(
                        $failure,
                        new RuntimeException(
                            "Unable to clean the remote feed staging directory: [$remoteStaging].",
                            previous: $e
                        )
                    );
                }
            }

            if ($failure !== null) {
                throw $failure;
            }
        });
    }

    /** @param  resource  $resource */
    public function close($resource): void // @pest-ignore-type
    {
        if (! is_resource($resource)) {
            // @codeCoverageIgnoreStart
            return;
            // @codeCoverageIgnoreEnd
        }

        fclose($resource);
    }

    protected function cleanTemporaryDirectory(string $filename): void
    {
        $this->file->deleteDirectory(
            dirname($filename)
        );
    }

    protected function backup(string $path, string $staging): string
    {
        $directory = $staging . DIRECTORY_SEPARATOR . 'backups';

        $this->file->ensureDirectoryExists($directory);

        if (! $this->file->isDirectory($directory)) {
            throw new RuntimeException("Unable to create the feed backup directory: [$directory].");
        }

        $backup = $directory . DIRECTORY_SEPARATOR . $this->temporaryFilename(basename($path));

        if (! $this->file->move($path, $backup)) {
            throw new RuntimeException("Unable to back up the published feed: [$path].");
        }

        return $backup;
    }

    protected function commitStorage(
        FilesystemOperator $storage,
        string $path,
        mixed $drafts,
        string $staging,
        bool &$cleanup,
    ): void {
        $drafts = $this->validateStorageDrafts($path, $drafts);

        $cleanup   = true;
        $backups   = [];
        $installed = [];

        try {
            $drafts   = $this->stageStorageDrafts($storage, $drafts, $staging);
            $existing = $this->publishedStoragePaths($storage, $path);
            $targets  = array_keys($drafts);

            foreach ($drafts as $target => $draft) {
                if ($storage->fileExists($target)) {
                    $backup           = $this->storageBackupPath($storage, $staging);
                    $backups[$target] = $backup;

                    $storage->move($target, $backup);
                }

                $installed[] = $target;

                $storage->move($draft, $target);
            }

            foreach ($existing as $published) {
                if ($this->containsStoragePath($targets, $published) || ! $storage->fileExists($published)) {
                    continue;
                }

                $backup              = $this->storageBackupPath($storage, $staging);
                $backups[$published] = $backup;

                $storage->move($published, $backup);
            }
        } catch (Throwable $e) {
            $rollbackFailure = $this->rollbackStorage($storage, $installed, $backups);

            if ($rollbackFailure !== null) {
                $cleanup = false;

                throw new CloseFeedException(
                    $path,
                    new RuntimeException(
                        $e->getMessage() . ' Rollback failed: ' . $rollbackFailure->getMessage()
                        . " Backups were preserved at: [$staging].",
                        previous: $e
                    )
                );
            }

            throw new CloseFeedException($path, $e);
        }
    }

    protected function commit(string $path, array $drafts, string $staging, bool &$cleanup): void
    {
        $drafts   = $this->validateDrafts($path, $drafts);
        $existing = $this->publishedPaths($path);
        $targets  = array_keys($drafts);

        $backups   = [];
        $installed = [];

        try {
            foreach ($drafts as $target => $draft) {
                if ($this->file->exists($target)) {
                    $backups[$target] = $this->backup($target, $staging);
                }

                $this->file->ensureDirectoryExists(dirname($target));

                if (! $this->file->move($draft, $target)) {
                    throw new RuntimeException("Unable to publish the staged feed: [$draft] to [$target].");
                }

                $installed[] = $target;
            }

            foreach ($existing as $published) {
                if ($this->containsPath($targets, $published) || ! $this->file->exists($published)) {
                    continue;
                }

                $backups[$published] = $this->backup($published, $staging);
            }
        } catch (Throwable $e) {
            $rollbackFailure = $this->rollback($installed, $backups);

            if ($rollbackFailure !== null) {
                $cleanup = false;

                throw new CloseFeedException(
                    $path,
                    new RuntimeException(
                        $e->getMessage() . ' Rollback failed: ' . $rollbackFailure->getMessage()
                        . " Backups were preserved at: [$staging].",
                        previous: $e
                    )
                );
            }

            throw new CloseFeedException($path, $e);
        }
    }

    protected function containsPath(array $paths, string $expected): bool
    {
        $key = $this->pathKey($expected);

        foreach ($paths as $path) {
            if ($this->pathKey($path) === $key) {
                return true;
            }
        }

        return false;
    }

    protected function containsStoragePath(array $paths, string $expected): bool
    {
        $key = $this->storagePathKey($expected);

        foreach ($paths as $path) {
            if ($this->storagePathKey($path) === $key) {
                return true;
            }
        }

        return false;
    }

    protected function createStagingDirectory(string $path): TemporaryDirectory
    {
        try {
            $this->file->ensureDirectoryExists(dirname($path));

            return $this->createTemporaryDirectory(
                dirname($path),
                fn () => '.feeds_staging_' . $this->uniqueIdentifier()
            );
        } catch (Throwable $e) {
            throw new OpenFeedException($path, $e);
        }
    }

    protected function draftPath(string $filename, ?string $directory = null): string
    {
        $draft = $this->createTemporaryDirectory(
            $directory ?? sys_get_temp_dir(),
            fn () => $this->temporaryFilename($filename)
        );

        try {
            return $draft->path() . DIRECTORY_SEPARATOR . $this->temporaryFilename($filename);
        } catch (Throwable $e) {
            $draft->delete();

            throw $e;
        }
    }

    protected function createTemporaryDirectory(string $location, Closure $name): TemporaryDirectory
    {
        for ($attempt = 0; $attempt < self::MAX_PATH_ATTEMPTS; $attempt++) {
            $directory = (new TemporaryDirectory)
                ->location($location)
                ->name($name());

            if ($this->file->makeDirectory($directory->path(), 0o777, false, true)) {
                return $directory;
            }

            if (! $this->file->exists($directory->path())) {
                throw new RuntimeException("Unable to create the temporary directory: [{$directory->path()}].");
            }
        }

        throw new RuntimeException(
            'Unable to create a unique temporary directory after [' . self::MAX_PATH_ATTEMPTS . '] attempts.'
        );
    }

    protected function isPublicationPath(string $path, string $publication): bool
    {
        if ($this->pathKey($path) === $this->pathKey($publication)) {
            return true;
        }

        if ($this->pathKey(dirname($path)) !== $this->pathKey(dirname($publication))) {
            return false;
        }

        return $this->matchesSplitFilename(basename($path), basename($publication));
    }

    protected function lockPath(string $path): string
    {
        return storage_path(
            'framework/cache/laravel-feeds/' . hash('sha256', $this->pathKey($path)) . '.lock'
        );
    }

    protected function matchesSplitFilename(string $filename, string $publication): bool
    {
        $basename  = pathinfo($publication, PATHINFO_FILENAME);
        $extension = pathinfo($publication, PATHINFO_EXTENSION);
        $suffix    = $extension === '' ? '' : '\\.' . preg_quote($extension, '~');

        return preg_match(
            '~^' . preg_quote($basename, '~') . '-[1-9][0-9]*' . $suffix . '$~D',
            $filename
        ) === 1;
    }

    protected function pathKey(string $path): string
    {
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        $resolved = realpath($path);

        if ($resolved !== false) {
            $path = $resolved;
        } elseif (($directory = realpath(dirname($path))) !== false) {
            $path = $directory . DIRECTORY_SEPARATOR . basename($path);
        }

        return DIRECTORY_SEPARATOR === '\\' ? strtolower($path) : $path;
    }

    protected function publishedStoragePaths(FilesystemOperator $storage, string $path): array
    {
        $paths = [];

        if ($storage->fileExists($path)) {
            $paths[] = $path;
        }

        foreach ($storage->listContents($this->storageDirectory($path), false) as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $candidate = $file->path();

            if ($this->matchesSplitFilename(basename($candidate), basename($path))) {
                $paths[] = $candidate;
            }
        }

        sort($paths, SORT_NATURAL);

        return $paths;
    }

    protected function publishedPaths(string $path): array
    {
        $paths = [];

        if ($this->file->exists($path)) {
            $paths[] = $path;
        }

        foreach ($this->file->files(dirname($path)) as $file) {
            if ($this->matchesSplitFilename($file->getFilename(), basename($path))) {
                $paths[] = $file->getPathname();
            }
        }

        sort($paths, SORT_NATURAL);

        return $paths;
    }

    protected function rollback(array $installed, array $backups): ?Throwable
    {
        $failures = [];

        foreach (array_reverse($installed) as $path) {
            try {
                if ($this->file->exists($path) && ! $this->file->delete($path)) {
                    throw new RuntimeException("Unable to remove the new feed during rollback: [$path].");
                }
            } catch (Throwable $e) {
                $failures[] = $e;
            }
        }

        foreach (array_reverse($backups, true) as $path => $backup) {
            try {
                if ($this->file->exists($path) && ! $this->file->delete($path)) {
                    throw new RuntimeException("Unable to clear the feed path during rollback: [$path].");
                }

                if (! $this->file->move($backup, $path)) {
                    throw new RuntimeException("Unable to restore the published feed during rollback: [$path].");
                }
            } catch (Throwable $e) {
                $failures[] = $e;
            }
        }

        if ($failures === []) {
            return null;
        }

        $messages = [];

        foreach ($failures as $failure) {
            $messages[] = get_class($failure) . ': ' . $failure->getMessage();
        }

        return new RuntimeException(implode('; ', $messages), previous: $failures[0]);
    }

    protected function rollbackStorage(FilesystemOperator $storage, array $installed, array $backups): ?Throwable
    {
        $failures = [];

        foreach (array_reverse($installed) as $path) {
            try {
                if ($storage->fileExists($path)) {
                    $storage->delete($path);
                }
            } catch (Throwable $e) {
                $failures[] = $e;
            }
        }

        foreach (array_reverse($backups, true) as $path => $backup) {
            try {
                $targetExists = $storage->fileExists($path);

                if (! $storage->fileExists($backup)) {
                    if (! $targetExists) {
                        throw new RuntimeException("Feed backup is missing during rollback: [$backup].");
                    }

                    continue;
                }

                if ($targetExists) {
                    $storage->delete($path);
                }

                $storage->move($backup, $path);
            } catch (Throwable $e) {
                $failures[] = $e;
            }
        }

        if ($failures === []) {
            return null;
        }

        $messages = [];

        foreach ($failures as $failure) {
            $messages[] = get_class($failure) . ': ' . $failure->getMessage();
        }

        return new RuntimeException(implode('; ', $messages), previous: $failures[0]);
    }

    protected function stageStorageDrafts(FilesystemOperator $storage, array $drafts, string $staging): array
    {
        $staged = [];

        foreach ($drafts as $target => $draft) {
            $path     = $staging . '/drafts/' . $this->uniqueIdentifier();
            $resource = @fopen($draft, 'rb');

            if ($resource === false) {
                throw new RuntimeException("Unable to open the staged feed for reading: [$draft].");
            }

            try {
                $storage->writeStream($path, $resource);
            } finally {
                fclose($resource);
            }

            $staged[$target] = $path;
        }

        return $staged;
    }

    protected function storageBackupPath(FilesystemOperator $storage, string $staging): string
    {
        $directory = $staging . '/backups';

        if (! $storage->directoryExists($directory)) {
            $storage->createDirectory($directory);
        }

        return $directory . '/' . $this->uniqueIdentifier();
    }

    protected function storageDirectory(string $path): string
    {
        $directory = pathinfo($path, PATHINFO_DIRNAME);

        return $directory === '.' ? '' : $directory;
    }

    protected function storagePathKey(string $path): string
    {
        return str_replace('\\', '/', $path);
    }

    protected function storageStagingPath(string $path): string
    {
        $directory = $this->storageDirectory($path);
        $staging   = '.feeds_staging_' . $this->uniqueIdentifier();

        return $directory === '' ? $staging : "$directory/$staging";
    }

    protected function temporaryFilename(string $filename): string
    {
        return 'feeds_draft_' . $this->uniqueIdentifier();
    }

    protected function uniqueIdentifier(): string
    {
        return bin2hex(random_bytes(16));
    }

    protected function validateDrafts(string $path, array $drafts): array
    {
        return $this->validateDraftMap(
            $path,
            $drafts,
            fn (string $target, string $publication) => $this->isPublicationPath($target, $publication),
            fn (string $target)                      => $this->pathKey($target),
        );
    }

    protected function validateStorageDrafts(string $path, mixed $drafts): array
    {
        if (! is_array($drafts)) {
            throw new RuntimeException('The publication callback must return an array of staged files.');
        }

        return $this->validateDraftMap(
            $path,
            $drafts,
            fn (string $target, string $publication) => $this->isStoragePublicationPath($target, $publication),
            fn (string $target)                      => $this->storagePathKey($target),
        );
    }

    protected function validateDraftMap(
        string $path,
        array $drafts,
        Closure $isPublicationPath,
        Closure $targetPathKey,
    ): array {
        if ($drafts === []) {
            throw new RuntimeException("No staged feeds were provided for publication: [$path].");
        }

        $targets   = [];
        $sources   = [];
        $validated = [];

        foreach ($drafts as $target => $draft) {
            if (! is_string($target) || ! is_string($draft)) {
                throw new RuntimeException('Staged feed paths and publication targets must be strings.');
            }

            if (! $isPublicationPath($target, $path)) {
                throw new RuntimeException("Invalid feed publication target: [$target].");
            }

            if (! $this->file->isFile($draft)) {
                throw new RuntimeException("Staged feed does not exist: [$draft].");
            }

            $targetKey = $targetPathKey($target);
            $sourceKey = $this->pathKey($draft);

            if (isset($targets[$targetKey])) {
                throw new RuntimeException("Duplicate feed publication target: [$target].");
            }

            if (isset($sources[$sourceKey])) {
                throw new RuntimeException("Duplicate staged feed: [$draft].");
            }

            $targets[$targetKey] = true;
            $sources[$sourceKey] = true;
            $validated[$target]  = $draft;
        }

        return $validated;
    }

    protected function isStoragePublicationPath(string $path, string $publication): bool
    {
        if ($this->storagePathKey($path) === $this->storagePathKey($publication)) {
            return true;
        }

        if ($this->storagePathKey(dirname($path)) !== $this->storagePathKey(dirname($publication))) {
            return false;
        }

        return $this->matchesSplitFilename(basename($path), basename($publication));
    }

    protected function withCleanupFailure(?Throwable $failure, Throwable $cleanupFailure): Throwable
    {
        if ($failure === null) {
            return $cleanupFailure;
        }

        return new RuntimeException(
            $failure->getMessage() . ' ' . $cleanupFailure->getMessage(),
            previous: $failure
        );
    }

    /** @param  resource  $file */
    protected function getMetaPath($file): string // @pest-ignore-type
    {
        $meta = stream_get_meta_data($file);

        return $meta['uri'] ?? throw new ResourceMetaException;
    }
}
