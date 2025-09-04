<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Services;

use DragonCode\LaravelFeed\Converters\ConvertToXml;
use DragonCode\LaravelFeed\Data\ElementData;
use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Queries\FeedQuery;
use Illuminate\Database\Eloquent\Collection;

use function blank;
use function collect;
use function get_class;
use function implode;
use function sprintf;

class GeneratorService
{
    public function __construct(
        protected Filesystem $filesystem,
        protected ConvertToXml $converter,
        protected FeedQuery $query,
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

        $this->setLastActivity($feed);
    }

    protected function performItem($file, Feed $feed): void // @pest-ignore-type
    {
        $feed->builder()->chunkById($feed->chunkSize(), function (Collection $models) use ($file, $feed) {
            $content = [];

            foreach ($models as $model) {
                $content[] = $this->converter->convertItem(
                    $feed->item($model)
                );
            }

            $this->append($file, implode(PHP_EOL, $content), $feed->path());
        });
    }

    protected function performHeader($file, Feed $feed): void // @pest-ignore-type
    {
        $this->append($file, $feed->header(), $feed->path());
    }

    protected function performInfo($file, Feed $feed): void // @pest-ignore-type
    {
        if (blank($info = $feed->info()->toArray())) {
            return;
        }

        $value = $this->converter->convertInfo($info);

        $this->append($file, PHP_EOL . $value, $feed->path());
    }

    protected function performRoot($file, Feed $feed): void // @pest-ignore-type
    {
        if (! $name = $feed->root()->name) {
            return;
        }

        $value = ! empty($feed->root()->attributes)
            ? sprintf("\n<%s %s>\n", $name, $this->makeRootAttributes($feed->root()))
            : sprintf("\n<%s>\n", $name);

        $this->append($file, $value, $feed->path());
    }

    protected function performFooter($file, Feed $feed): void // @pest-ignore-type
    {
        $value = '';

        if ($name = $feed->root()->name) {
            $value .= "\n</$name>\n";
        }

        $value .= $feed->footer();

        $this->append($file, $value, $feed->path());
    }

    protected function makeRootAttributes(ElementData $item): string
    {
        return collect($item->attributes)
            ->map(fn (mixed $value, int|string $key) => sprintf('%s="%s"', $key, $value))
            ->implode(' ');
    }

    protected function append($file, string $content, string $path): void // @pest-ignore-type
    {
        $this->filesystem->append($file, $content, $path);
    }

    protected function release($file, string $path): void // @pest-ignore-type
    {
        $this->filesystem->release($file, $path);
    }

    protected function openFile(string $path) // @pest-ignore-type
    {
        return $this->filesystem->open($path);
    }

    protected function setLastActivity(Feed $feed): void
    {
        $this->query->setLastActivity(
            get_class($feed)
        );
    }
}
