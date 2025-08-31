<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Services;

use DragonCode\LaravelFeed\Data\ElementData;
use DragonCode\LaravelFeed\Feeds\Feed;
use Illuminate\Database\Eloquent\Collection;

use function blank;
use function collect;
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
            $path = $feed->path()
        );

        $this->performHeader($file, $feed);
        $this->performInfo($file, $feed);
        $this->performRoot($file, $feed);
        $this->performItem($file, $feed);
        $this->performFooter($file, $feed);

        $this->release($file, $path);
    }

    protected function performItem($file, Feed $feed): void
    {
        $feed->builder()->chunkById($feed->chunkSize(), function (Collection $models) use ($file, $feed) {
            $content = [];

            foreach ($models as $model) {
                $content[] = $this->converter->convertItem(
                    $feed->item($model)
                );
            }

            $this->append($file, implode(PHP_EOL, $content));
        });
    }

    protected function performHeader($file, Feed $feed): void
    {
        $this->append($file, $feed->header());
    }

    protected function performInfo($file, Feed $feed): void
    {
        if (blank($info = $feed->info()->toArray())) {
            return;
        }

        $value = $this->converter->convertInfo($info);

        $this->append($file, PHP_EOL . $value);
    }

    protected function performRoot($file, Feed $feed): void
    {
        if (! $name = $feed->root()->name) {
            return;
        }

        $value = ! empty($feed->root()->attributes)
            ? sprintf("\n<%s %s>\n", $name, $this->makeRootAttributes($feed->root()))
            : sprintf("\n<%s>\n", $name);

        $this->append($file, $value);
    }

    protected function performFooter($file, Feed $feed): void
    {
        $value = '';

        if ($name = $feed->root()->name) {
            $value .= "\n</$name>\n";
        }

        $this->append($file, $value . $feed->footer());
    }

    protected function makeRootAttributes(ElementData $item): string
    {
        return collect($item->attributes)
            ->map(fn (mixed $value, int|string $key) => sprintf('%s="%s"', $key, $value))
            ->implode(' ');
    }

    protected function append($file, string $content): void
    {
        $this->filesystem->append($file, $content);
    }

    protected function release($file, string $path): void
    {
        $this->filesystem->release($file, $path);
    }

    protected function openFile(string $path)
    {
        return $this->filesystem->open($path);
    }
}
