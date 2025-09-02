<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Services;

use DragonCode\LaravelFeed\Exceptions\OpenFeedException;
use DragonCode\LaravelFeed\Exceptions\WriteFeedException;
use Illuminate\Filesystem\Filesystem as File;

use function blank;
use function dirname;
use function fclose;
use function fopen;
use function fwrite;
use function is_resource;

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

        $resource = fopen($path, 'ab');

        if ($resource === false) {
            throw new OpenFeedException($path);
        }

        return $resource;
    }

    /**
     * @param  resource  $resource
     */
    public function append($resource, string $content, string $path): void
    {
        if (blank($content)) {
            return;
        }

        if (fwrite($resource, $content) === false) {
            throw new WriteFeedException($path);
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
        if (! is_resource($resource)) {
            return;
        }

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
