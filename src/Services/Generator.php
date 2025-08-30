<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Services;

use DragonCode\LaravelFeed\Data\ElementData;
use DragonCode\LaravelFeed\Feeds\Feed;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Filesystem\Filesystem;

use function collect;
use function dirname;
use function fclose;
use function fopen;
use function fwrite;
use function implode;
use function sprintf;

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

        $this->performHeader($file, $feed);
        $this->performItem($file, $feed);
        $this->performFooter($file, $feed);

        $this->closeFile($file);
        $this->release($feed, $filename);
    }

    protected function performItem($file, Feed $feed): void
    {
        $feed->builder()->chunkById($feed->chunkSize(), function (Collection $models) use ($file, $feed) {
            $content = [];

            foreach ($models as $model) {
                $content[] = $this->converter->convert(
                    $feed->item($model)
                );
            }

            $this->append($file, implode(PHP_EOL, $content));
        });
    }

    protected function performHeader($file, Feed $feed): void
    {
        $value = $feed->header();

        if ($name = $feed->root()->name) {
            $value .= ! empty($feed->root()->attributes)
                ? sprintf("\n<%s %s>\n", $name, $this->makeRootAttributes($feed->root()))
                : sprintf("\n<%s>\n", $name);
        }

        $this->append($file, $value);
    }

    protected function performFooter($file, Feed $feed): void
    {
        $value = $feed->footer();

        if ($name = $feed->root()->name) {
            $value .= "\n</$name>\n";
        }

        $this->append($file, $value);
    }

    protected function makeRootAttributes(ElementData $item): string
    {
        return collect($item->attributes)
            ->map(fn (mixed $value, int|string $key) => sprintf('%s="%s"', $key, $value))
            ->implode(' ');
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
