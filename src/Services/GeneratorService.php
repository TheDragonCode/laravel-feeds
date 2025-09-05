<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Services;

use DragonCode\LaravelFeed\Converters\Converter;
use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Helpers\ConverterHelper;
use DragonCode\LaravelFeed\Queries\FeedQuery;
use Illuminate\Console\OutputStyle;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\Console\Helper\ProgressBar;

use function blank;
use function get_class;
use function implode;

class GeneratorService
{
    public function __construct(
        protected FilesystemService $filesystem,
        protected ConverterHelper $converter,
        protected FeedQuery $query,
    ) {}

    public function feed(Feed $feed, ?OutputStyle $output = null): void
    {
        $file = $this->openFile(
            $path = $feed->path()
        );

        $this->performHeader($file, $feed);
        $this->performRoot($file, $feed, true);
        $this->performInfo($file, $feed);
        $this->performRoot($file, $feed, false);
        $this->performItem($file, $feed, $output);
        $this->performFooter($file, $feed);

        $this->release($file, $path);

        $this->setLastActivity($feed);
    }

    protected function performItem($file, Feed $feed, ?OutputStyle $output): void // @pest-ignore-type
    {
        $count = $feed->builder()->count();

        // @codeCoverageIgnoreStart
        $bar = $this->progressBar($count, $output);
        // @codeCoverageIgnoreEnd

        $progress = $count;

        $feed->builder()->chunkById(
            $feed->chunkSize(),
            function (Collection $models) use ($file, $feed, $bar, &$progress) {
                $content = [];

                foreach ($models as $model) {
                    $content[] = $this->converter($feed)->item(
                        item: $feed->item($model),
                        isLast: $progress <= 1
                    );

                    $bar?->advance();
                    $progress--;
                }

                $this->append($file, implode(PHP_EOL, $content), $feed->path());
            }
        );

        $bar?->finish();
        $output?->newLine();
    }

    protected function performHeader($file, Feed $feed): void // @pest-ignore-type
    {
        $value = $this->converter($feed)->header($feed);

        $this->append($file, $value, $feed->path());
    }

    protected function performInfo($file, Feed $feed): void // @pest-ignore-type
    {
        if (blank($info = $feed->info()->toArray())) {
            return;
        }

        $value = $this->converter($feed)->info($info, $feed->root()->beforeInfo);

        $this->append($file, $value . PHP_EOL, $feed->path());
    }

    protected function performRoot($file, Feed $feed, bool $when): void // @pest-ignore-type
    {
        if ($feed->root()->beforeInfo !== $when) {
            return;
        }

        if (! $feed->root()->name) {
            return;
        }

        $value = $this->converter($feed)->root($feed);

        $this->append($file, $value, $feed->path());
    }

    protected function performFooter($file, Feed $feed): void // @pest-ignore-type
    {
        $value = $this->converter($feed)->footer($feed);

        $this->append($file, $value, $feed->path());
    }

    protected function append($file, string $content, string $path): void // @pest-ignore-type
    {
        if (blank($content)) {
            return;
        }

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

    protected function converter(Feed $feed): Converter
    {
        return $this->converter->get(
            $feed->format()
        );
    }

    protected function progressBar(int $count, ?OutputStyle $output): ?ProgressBar
    {
        return $output?->createProgressBar($count);
    }
}
