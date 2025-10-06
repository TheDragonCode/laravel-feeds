<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Services;

use Closure;
use DragonCode\LaravelFeed\Converters\Converter;
use DragonCode\LaravelFeed\Events\FeedFinishedEvent;
use DragonCode\LaravelFeed\Events\FeedStartingEvent;
use DragonCode\LaravelFeed\Exceptions\FeedGenerationException;
use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Helpers\ConverterHelper;
use DragonCode\LaravelFeed\Queries\FeedQuery;
use Illuminate\Console\OutputStyle;
use Illuminate\Database\Eloquent\Model;
use Throwable;

use function blank;
use function event;
use function get_class;

class GeneratorService
{
    public function __construct(
        protected FilesystemService $filesystem,
        protected ConverterHelper $converter,
        protected FeedQuery $query,
    ) {}

    public function feed(Feed $feed, ?OutputStyle $output = null): void
    {
        try {
            $this->started($feed);

            $this->export($feed, $output, $this->filesystem);

            $this->setLastActivity($feed);

            $this->finished($feed, $feed->path());
        } catch (Throwable $e) {
            throw new FeedGenerationException(get_class($feed), $e);
        }
    }

    protected function export(Feed $feed, ?OutputStyle $output, FilesystemService $filesystem): void
    {
        (new ExportService($feed, $filesystem, $output))
            ->file(
                create: $this->createFile($feed),
                close : $this->closeFile($feed)
            )
            ->item(fn (Model $model, int $index) => $this->converter($feed)->item(
                item  : $feed->item($model),
                isLast: $index <= 1
            ))
            ->chunk($feed->chunkSize())
            ->export();
    }

    protected function createFile(Feed $feed): Closure
    {
        return function () use ($feed) {
            $file = $this->createDraft($feed->filename());

            $this->performHeader($file, $feed);
            $this->performRoot($file, $feed, true);
            $this->performInfo($file, $feed);
            $this->performRoot($file, $feed, false);

            return $file;
        };
    }

    protected function closeFile(Feed $feed): Closure
    {
        return function ($file, int $index) use ($feed) {
            $this->performFooter($file, $feed);

            $this->release($file, $feed->path($index));
        };
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

    protected function createDraft(string $filename) // @pest-ignore-type
    {
        return $this->filesystem->createDraft($filename);
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

    protected function started(Feed $feed): void
    {
        event(new FeedStartingEvent(get_class($feed)));
    }

    protected function finished(Feed $feed, string $path): void
    {
        event(new FeedFinishedEvent(get_class($feed), $path));
    }
}
