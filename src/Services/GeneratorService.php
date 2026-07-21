<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Services;

use Closure;
use DragonCode\LaravelFeed\Converters\Converter;
use DragonCode\LaravelFeed\Data\GenerationResultData;
use DragonCode\LaravelFeed\Events\FeedFinishedEvent;
use DragonCode\LaravelFeed\Events\FeedStartingEvent;
use DragonCode\LaravelFeed\Exceptions\FeedGenerationException;
use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Helpers\ConverterHelper;
use DragonCode\LaravelFeed\Queries\FeedQuery;
use Illuminate\Console\OutputStyle;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;
use Throwable;

use function array_keys;
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

    public function feed(Feed $feed, ?OutputStyle $output = null): GenerationResultData
    {
        $class   = get_class($feed);
        $storage = $feed->storage();
        $path    = $feed->path();

        $this->debug($output, 'Generation started.', [
            'feed' => $class,
            'path' => $path,
        ]);

        try {
            $this->started($feed);

            $converter = $this->converter($feed);
            $result    = null;

            $this->filesystem->publishTo(
                $storage,
                $feed->storagePath(),
                function (string $staging) use ($feed, $output, $converter, &$result) {
                    $this->debug($output, 'Publication lock acquired and staging created.', [
                        'feed'    => get_class($feed),
                        'staging' => $staging,
                    ]);

                    $drafts = [];
                    $result = $this->export($feed, $output, $this->filesystem, $staging, $drafts, $converter);

                    $this->debug($output, 'All feed parts staged.', [
                        'feed'    => get_class($feed),
                        'parts'   => count($result->paths),
                        'paths'   => $result->paths,
                        'records' => $result->records,
                    ]);

                    return $drafts;
                }
            );

            if (! $result instanceof GenerationResultData) {
                throw new RuntimeException('Feed generation did not produce a result.');
            }

            $this->debug($output, 'Publication committed.', [
                'feed'  => $class,
                'paths' => $result->paths,
            ]);

            $this->setLastActivity($feed);

            $this->finished($feed, $result);

            $this->debug($output, 'Generation finished.', [
                'feed'    => $class,
                'paths'   => $result->paths,
                'records' => $result->records,
            ]);

            return $result;
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
        string $staging,
        array &$drafts,
        Converter $converter,
    ): GenerationResultData {
        $records = [];

        (new ExportService($feed, $filesystem, $output))
            ->file(
                create: $this->createFile($feed, $staging, $converter),
                close : $this->closeFile($feed, $drafts, $records, $converter)
            )
            ->item(fn (Model $model, bool $last) => $converter->item(
                item  : $feed->item($model),
                isLast: $last
            ))
            ->lineEnding($converter->lineEnding())
            ->chunk($feed->chunkSize())
            ->export();

        return new GenerationResultData(
            paths  : array_keys($records),
            records: $records,
        );
    }

    protected function createFile(Feed $feed, string $staging, Converter $converter): Closure
    {
        return function () use ($feed, $staging, $converter) {
            $file = $this->createDraft($feed->filename(), $staging);

            try {
                $this->performHeader($file, $feed, $converter);
                $this->performRoot($file, $feed, $converter, true);
                $this->performInfo($file, $feed, $converter);
                $this->performRoot($file, $feed, $converter, false);

                return $file;
            } catch (Throwable $e) {
                $this->filesystem->close($file);

                throw $e;
            }
        };
    }

    protected function closeFile(Feed $feed, array &$drafts, array &$records, Converter $converter): Closure
    {
        return function ($file, int $index, int $count) use ($feed, &$drafts, &$records, $converter) {
            $this->performFooter($file, $feed, $converter);

            $storagePath = $feed->storagePath($index);
            $path        = $feed->path($index);

            $drafts[$storagePath] = $this->filesystem->finishDraft($file);
            $records[$path]       = $count;
        };
    }

    protected function debug(?OutputStyle $output, string $message, array $context): void
    {
        if (! $output?->isDebug()) {
            return;
        }

        $output->writeln('[laravel-feeds] ' . $message . ' ' . json_encode($context));
    }

    protected function performHeader($file, Feed $feed, Converter $converter): void // @pest-ignore-type
    {
        $value = $converter->header($feed);

        $this->append($file, $value, $feed->path());
    }

    protected function performInfo($file, Feed $feed, Converter $converter): void // @pest-ignore-type
    {
        if (blank($info = $feed->info()->toArray())) {
            return;
        }

        $value = $converter->info($info, $feed->root()->beforeInfo);

        $this->append($file, $value . $converter->lineEnding(), $feed->path());
    }

    protected function performRoot($file, Feed $feed, Converter $converter, bool $when): void // @pest-ignore-type
    {
        if ($feed->root()->beforeInfo !== $when) {
            return;
        }

        if (! $feed->root()->name) {
            return;
        }

        $value = $converter->root($feed);

        $this->append($file, $value, $feed->path());
    }

    protected function performFooter($file, Feed $feed, Converter $converter): void // @pest-ignore-type
    {
        $value = $converter->footer($feed);

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

    protected function finished(Feed $feed, GenerationResultData $result): void
    {
        event(new FeedFinishedEvent(get_class($feed), $result->paths[0], $result->paths));
    }
}
