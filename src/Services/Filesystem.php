<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Services;

use Illuminate\Filesystem\Filesystem as File;

use function dirname;
use function fclose;
use function fopen;
use function fwrite;

class Filesystem
{
    public function __construct(
        protected File $file,
    ) {}

    /**
     * @return resource
     */
    public function open(string $path)
    {
        $path = $this->draft($path);

        $this->ensureFileDelete($path);
        $this->ensureDirectory($path);

        return fopen($path, 'ab');
    }

    /**
     * @param  resource  $resource
     */
    public function append($resource, string $content): void
    {
        if (! empty($content)) {
            fwrite($resource, $content);
        }
    }

    /**
     * @param  resource  $resource
     */
    public function release($resource, string $path): void
    {
        $this->close($resource);

        if ($this->file->exists($path)) {
            $this->file->delete($path);
        }

        $this->file->move(
            $this->draft($path),
            $path
        );
    }

    /**
     * @param  resource  $resource
     */
    public function close($resource): void
    {
        fclose($resource);
    }

    protected function ensureFileDelete(string $path): void
    {
        if ($this->file->exists($path)) {
            $this->file->delete($path);
        }
    }

    protected function ensureDirectory(string $path): void
    {
        $this->file->ensureDirectoryExists(dirname($path));
    }

    protected function draft(string $path): string
    {
        return $path . '.draft';
    }
}
