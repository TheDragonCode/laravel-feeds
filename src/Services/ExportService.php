<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Services;

use Closure;
use DragonCode\LaravelFeed\Feeds\Feed;
use Illuminate\Console\OutputStyle;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Console\Helper\ProgressBar;

use function implode;
use function max;
use function value;

class ExportService
{
    protected int $chunk;

    protected readonly int $perFile;

    protected readonly int $maxFiles;

    protected readonly int $modelCount;

    protected int $total;

    protected int $file;

    protected Closure $createFile;

    protected Closure $closeFile;

    protected Closure $item;

    protected ?ProgressBar $progressBar;

    /** @var resource */
    protected $resource;

    protected int $records = 0;

    protected int $left;

    protected array $content = [];

    protected bool $fileCreated = false;

    public function __construct(
        protected Feed $feed,
        protected FilesystemService $filesystem,
        protected ?OutputStyle $output,
    ) {
        $this->perFile  = $this->perFile($this->feed);
        $this->maxFiles = $this->maxFiles($this->feed);
        $this->total    = $this->total();
        $this->file     = $this->fileIndex();

        $this->left = $this->total;

        $this->progressBar = $this->createProgressBar(
            $this->total
        );
    }

    public function chunk(int $chunk): static
    {
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
        $this->feed->builder()
            ->lazyById($this->chunk)
            ->each(function (Model $model) {
                $this->records++;
                $this->left--;

                $this->content[] = value($this->item, $model, $this->isLastItem());

                $this->store();

                if ($this->left <= 0) {
                    return false;
                }

                if ($this->maxFiles && $this->file >= $this->maxFiles) {
                    return false;
                }
            });

        $this->store(true);

        $this->progressBar?->finish();
    }

    protected function store(bool $force = false): void
    {
        $whenRecords = $this->records >= $this->perFile;
        $whenLeft    = $this->total    && $this->left <= 0;
        $whenFile    = $this->file > 1 && ! $this->content;

        if (! $force && $whenFile) {
            return;
        }

        if ($force || $whenRecords || $whenLeft) {
            $this->records = 0;

            if ($this->content || ! $this->fileCreated) {
                $this->append();
            }

            $this->content = [];
        }

        if ($force || $whenRecords) {
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

        value($this->closeFile, $this->resource, $this->file);

        $this->resource = null;

        $this->file++;
    }

    protected function append(): void
    {
        $this->filesystem->append($this->getFile(), implode(PHP_EOL, $this->content), $this->feed->path());
    }

    protected function perFile(Feed $feed): int
    {
        if ($count = max($feed->perFile(), 0)) {
            return $count;
        }

        return $this->modelCount();
    }

    protected function maxFiles(Feed $feed): int
    {
        return max($feed->maxFiles(), 0);
    }

    protected function total(): int
    {
        if ($this->maxFiles === 0) {
            return $this->modelCount();
        }

        return $this->perFile * $this->maxFiles;
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
