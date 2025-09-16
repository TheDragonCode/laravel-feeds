<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Services;

use DragonCode\LaravelFeed\Exceptions\OpenFeedException;
use DragonCode\LaravelFeed\Exceptions\ResourceMetaException;
use DragonCode\LaravelFeed\Exceptions\WriteFeedException;
use Illuminate\Filesystem\Filesystem as File;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use Throwable;

use function dirname;
use function fclose;
use function fopen;
use function fwrite;
use function is_resource;
use function microtime;
use function stream_get_meta_data;

class FilesystemService
{
    public function __construct(
        protected File $file,
    ) {}

    /**
     * @return resource
     */
    public function createDraft(string $filename) // @pest-ignore-type
    {
        $temp = $this->draftPath($filename);

        try {
            $resource = fopen($temp, 'ab');

            if ($resource === false) {
                // @codeCoverageIgnoreStart
                throw new RuntimeException('Unable to open resource for writing.');
                // @codeCoverageIgnoreEnd
            }

            return $resource;
            // @codeCoverageIgnoreStart
        } catch (Throwable $e) {
            throw new OpenFeedException($temp, $e);
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * @param  resource  $resource
     */
    public function append($resource, string $content, string $path): void // @pest-ignore-type
    {
        if (fwrite($resource, $content) === false) {
            // @codeCoverageIgnoreStart
            throw new WriteFeedException($path);
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * @param  resource  $resource
     */
    public function release($resource, string $path): void // @pest-ignore-type
    {
        $temp = $this->getMetaPath($resource);

        $this->close($resource);

        if ($this->file->exists($path)) {
            $this->file->delete($path);
        }

        $this->file->ensureDirectoryExists(
            dirname($path)
        );

        $this->file->move($temp, $path);

        $this->cleanTemporaryDirectory($temp);
    }

    /**
     * @param  resource  $resource
     */
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

    protected function draftPath(string $filename): string
    {
        return (new TemporaryDirectory)
            ->name($this->temporaryFilename($filename))
            ->create()
            ->path((string) microtime(true));
    }

    protected function temporaryFilename(string $filename): string
    {
        return Str::of($filename)
            ->prepend('feeds_draft_')
            ->append('_', microtime(true))
            ->slug('_')
            ->toString();
    }

    /**
     * @param  resource  $file
     */
    protected function getMetaPath($file): string
    {
        $meta = stream_get_meta_data($file);

        return $meta['uri'] ?? throw new ResourceMetaException;
    }
}
