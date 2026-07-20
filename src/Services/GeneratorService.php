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
use function count;
use function event;
use function get_class;
use function json_encode;

class GeneratorService
{
    public function __construct(
        protected FilesystemService $filesystem,
        protected ConverterHelper $converter,
        protected FeedQuery $query,
    ) {}

    public function feed(Feed $feed, ?OutputStyle $output = null): void
    {
        $class = get_class($feed);
        $path  = $feed->path();

        $this->debug($output, 'Generation started.', [
            'feed' => $class,
            'path' => $path,
        ]);

        try {
            $this->started($feed);

            $this->filesystem->publish($path, function (string $staging) use ($feed, $output) {
                $this->debug($output, 'Publication lock acquired and staging created.', [
                    'feed'    => get_class($feed),
                    'staging' => $staging,
                ]);

                $drafts = $this->export($feed, $output, $this->filesystem, $staging);

                $this->debug($output, 'All feed parts staged.', [
                    'feed'  => get_class($feed),
                    'parts' => count($drafts),
                ]);

                return $drafts;
            });

            $this->debug($output, 'Publication committed.', [
                'feed' => $class,
                'path' => $path,
            ]);

            $this->setLastActivity($feed);

            $this->finished($feed, $path);

            $this->debug($output, 'Generation finished.', [
                'feed' => $class,
                'path' => $path,
            ]);
        } catch (Throwable $e) {
            $this->debug($output, 'Generation failed.', [
                'feed'      => $class,
                'path'      => $path,
                'exception' => get_class($e),
                'message'   => $e->getMessage(),
            ]);

            throw new FeedGenerationException($class, $e);
        }
    }

    protected function export(
        Feed $feed,
        ?OutputStyle $output,
        FilesystemService $filesystem,
        string $staging
    ): array {
        $drafts = [];

        (new ExportService($feed, $filesystem, $output))
            ->file(
                create: $this->createFile($feed, $staging),
                close : $this->closeFile($feed, $drafts)
            )
            ->item(fn (Model $model, bool $last) => $this->converter($feed)->item(
                item  : $feed->item($model),
                isLast: $last
            ))
            ->chunk($feed->chunkSize())
            ->export();

        return $drafts;
    }

    protected function createFile(Feed $feed, string $staging): Closure
    {
        return function () use ($feed, $staging) {
            $file = $this->createDraft($feed->filename(), $staging);

            try {
                $this->performHeader($file, $feed);
                $this->performRoot($file, $feed, true);
                $this->performInfo($file, $feed);
                $this->performRoot($file, $feed, false);

                return $file;
            } catch (Throwable $e) {
                $this->filesystem->close($file);

                throw $e;
            }
        };
    }

    protected function closeFile(Feed $feed, array &$drafts): Closure
    {
        return function ($file, int $index) use ($feed, &$drafts) {
            $this->performFooter($file, $feed);

            $drafts[$feed->path($index)] = $this->filesystem->finishDraft($file);
        };
    }

    protected function debug(?OutputStyle $output, string $message, array $context): void
    {
        if (! $output?->isDebug()) {
            return;
        }

        $output->writeln('[laravel-feeds] ' . $message . ' ' . json_encode($context));
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

    protected function createDraft(string $filename, string $staging) // @pest-ignore-type
    {
        return $this->filesystem->createDraft($filename, $staging);
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
