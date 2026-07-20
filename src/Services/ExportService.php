<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Services;

use Closure;
use DragonCode\LaravelFeed\Feeds\Feed;
use Illuminate\Console\OutputStyle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Symfony\Component\Console\Helper\ProgressBar;
use Throwable;

use function min;
use function value;

class ExportService
{
    protected int $chunk;

    protected readonly int $perFile;

    protected readonly int $maxFiles;

    protected readonly int $modelCount;

    protected int $total;

    protected int $fileIndex;

    protected Closure $createFile;

    protected Closure $closeFile;

    protected Closure $item;

    protected ?ProgressBar $progressBar;

    /** @var resource */
    protected $resource;

    protected int $records = 0;

    protected int $writtenFiles = 0;

    protected int $left;

    protected bool $fileCreated = false;

    public function __construct(
        protected Feed $feed,
        protected FilesystemService $filesystem,
        protected ?OutputStyle $output,
    ) {
        $this->perFile   = $this->perFile($this->feed);
        $this->maxFiles  = $this->maxFiles($this->feed);
        $this->total     = $this->total();
        $this->fileIndex = $this->fileIndex();

        $this->left = $this->total;

        $this->progressBar = $this->createProgressBar(
            $this->total
        );
    }

    public function chunk(int $chunk): static
    {
        if ($chunk <= 0) {
            Log::warning('[FIX:159] Invalid feed chunk size', [
                'feed'       => $this->feed::class,
                'chunk_size' => $chunk,
            ]);

            throw new InvalidArgumentException("Feed chunkSize must be greater than 0, [$chunk] given.");
        }

        $this->chunk = $chunk;

        return $this;
    }

    public function file(Closure $create, Closure $close): static
    {
        $this->createFile = $create;
        $this->closeFile  = $close;

        return $this;
    }

    public function item(Closure $callback): static
    {
        $this->item = $callback;

        return $this;
    }

    public function export(): void
    {
        Log::debug('[FIX:159] Feed export started', [
            'feed'        => $this->feed::class,
            'chunk_size'  => $this->chunk,
            'per_file'    => $this->perFile,
            'max_files'   => $this->maxFiles,
            'model_count' => $this->modelCount(),
            'total'       => $this->total,
        ]);

        try {
            $this->feed->builder()
                ->lazyById($this->chunk)
                ->each(function (Model $model) {
                    $this->records++;
                    $this->left--;

                    $this->append(
                        value($this->item, $model, $this->isLastItem())
                    );

                    $this->store();

                    if ($this->left <= 0) {
                        return false;
                    }

                    if ($this->maxFiles && $this->writtenFiles >= $this->maxFiles) {
                        return false;
                    }
                });

            $this->store(true);

            $this->progressBar?->finish();
        } catch (Throwable $e) {
            Log::error('[FIX:159] Feed export failed', [
                'feed'          => $this->feed::class,
                'written_files' => $this->writtenFiles,
                'exception'     => $e,
            ]);

            throw $e;
        }

        Log::debug('[FIX:159] Feed export completed', [
            'feed'          => $this->feed::class,
            'written_files' => $this->writtenFiles,
            'total'         => $this->total,
        ]);
    }

    protected function store(bool $force = false): void
    {
        $whenRecords = $this->records >= $this->perFile;

        if ($force && ! $this->fileCreated) {
            $this->getFile();
        }

        if ($force || $whenRecords) {
            $this->records = 0;
            $this->releaseFile();
        }
    }

    protected function isLastItem(): bool
    {
        return $this->records === $this->perFile || $this->left <= 0;
    }

    protected function getFile() // @pest-ignore-type
    {
        if (! empty($this->resource)) {
            return $this->resource;
        }

        $this->fileCreated = true;

        return $this->resource ??= value($this->createFile);
    }

    protected function releaseFile(): void
    {
        if ($this->resource === null) {
            return;
        }

        value($this->closeFile, $this->resource, $this->fileIndex);

        $this->resource = null;

        $this->writtenFiles++;

        Log::debug('[FIX:159] Feed file written', [
            'feed'          => $this->feed::class,
            'file_index'    => $this->fileIndex,
            'written_files' => $this->writtenFiles,
        ]);

        $this->fileIndex++;
    }

    protected function append(string $content): void
    {
        if ($this->records > 1) {
            $content = PHP_EOL . $content;
        }

        $this->filesystem->append($this->getFile(), $content, $this->feed->path());
    }

    protected function perFile(Feed $feed): int
    {
        $count = $feed->perFile();

        if ($count < 0) {
            Log::warning('[FIX:159] Invalid feed per-file limit', [
                'feed'     => $feed::class,
                'per_file' => $count,
            ]);

            throw new InvalidArgumentException("Feed perFile must be greater than or equal to 0, [$count] given.");
        }

        if ($count) {
            return $count;
        }

        return $this->modelCount();
    }

    protected function maxFiles(Feed $feed): int
    {
        $count = $feed->maxFiles();

        if ($count < 0) {
            Log::warning('[FIX:159] Invalid feed file limit', [
                'feed'      => $feed::class,
                'max_files' => $count,
            ]);

            throw new InvalidArgumentException("Feed maxFiles must be greater than or equal to 0, [$count] given.");
        }

        return $count;
    }

    protected function total(): int
    {
        if ($this->maxFiles === 0) {
            return $this->modelCount();
        }

        return min(
            $this->modelCount(),
            $this->perFile * $this->maxFiles
        );
    }

    protected function fileIndex(): int
    {
        if ($this->perFile === 0 || $this->perFile === $this->total) {
            return 0;
        }

        if ($this->perFile >= $this->total) {
            return 0;
        }

        return 1;
    }

    protected function modelCount(): int
    {
        return $this->modelCount ??= $this->feed->builder()->count();
    }

    protected function createProgressBar(int $total): ?ProgressBar
    {
        return $this->output?->createProgressBar($total);
    }
}
