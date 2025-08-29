<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Services;

use DragonCode\LaravelFeed\Feed;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Filesystem\Filesystem;

use function dirname;
use function fclose;
use function fopen;
use function fwrite;
use function implode;

class Generator
{
    public function __construct(
        protected Filesystem $filesystem,
        protected ConvertToXml $converter,
    ) {}

    public function feed(Feed $feed): void
    {
        $file = $this->openFile(
            $filename = $this->draft($feed)
        );

        $this->append($file, $feed->header());

        $this->perform($file, $feed);

        $this->append($file, $feed->footer());

        $this->closeFile($file);
        $this->release($feed, $filename);
    }

    protected function perform($file, Feed $feed): void
    {
        $feed->builder()->chunk($feed->chunkSize(), function (Collection $models) use ($file, $feed) {
            $content = [];

            foreach ($models as $model) {
                $content[] = $this->converter->convert(
                    $feed->item($model)->toArray()
                );
            }

            $this->append($file, implode(PHP_EOL, $content));
        });
    }

    protected function append($file, string $content): void
    {
        if (! empty($content)) {
            fwrite($file, $content);
        }
    }

    protected function release(Feed $feed, string $draft): void
    {
        if ($this->filesystem->exists($feed->path())) {
            $this->filesystem->delete($feed->path());
        }

        $this->filesystem->move($draft, $feed->path());
    }

    protected function openFile(string $filename)
    {
        $this->ensureDirectory($filename);

        return fopen($filename, 'ab');
    }

    protected function closeFile($file): void
    {
        fclose($file);
    }

    protected function ensureDirectory(string $filename): void
    {
        $this->filesystem->ensureDirectoryExists(dirname($filename));
    }

    protected function draft(Feed $feed): string
    {
        return $feed->path() . '.draft';
    }
}
