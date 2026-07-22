<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Services;

use Closure;
use DragonCode\LaravelFeed\Feeds\Feed;
use Illuminate\Console\OutputStyle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\LazyCollection;
use InvalidArgumentException;
use Symfony\Component\Console\Helper\ProgressBar;

use function intdiv;
use function is_resource;
use function min;
use function value;

class ExportService
{
    protected int $chunk;

    protected readonly int $perFile;

    protected readonly int $maxFiles;

    protected readonly ?int $capacity;

    protected readonly ?int $total;

    protected int $fileIndex = 0;

    protected Closure $createFile;

    protected Closure $closeFile;

    protected Closure $item;

    protected ?ProgressBar $progressBar;

    /** @var resource */
    protected $resource;

    protected int $records = 0;

    protected int $writtenFiles = 0;

    protected bool $fileCreated = false;

    protected string $lineEnding = PHP_EOL;

    protected ?int $modelCount = null;

    public function __construct(
        protected Feed $feed,
        protected FilesystemService $filesystem,
        protected ?OutputStyle $output,
    ) {
        $this->perFile  = $this->perFile($this->feed);
        $this->maxFiles = $this->maxFiles($this->feed);
        $this->capacity = $this->capacity();
        $this->total    = $this->output === null ? null : $this->total();

        $this->progressBar = $this->total === null ? null : $this->createProgressBar($this->total);
    }

    public function chunk(int $chunk): static
    {
        if ($chunk <= 0) {
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

    public function lineEnding(string $lineEnding): static
    {
        $this->lineEnding = $lineEnding;

        return $this;
    }

    public function export(): void
    {
        try {
            $pending = null;
            $models  = $this->models();

            if (($limit = $this->total ?? $this->capacity) !== null) {
                $models = $models->take($limit);
            }

            foreach ($models as $model) {
                if ($pending instanceof Model) {
                    $this->write($pending, true);
                }

                $pending = $model;
            }

            if ($pending instanceof Model) {
                $this->write($pending, false);
            }

            $this->store(true);

            $this->progressBar?->finish();
        } finally {
            if (is_resource($this->resource)) {
                $this->filesystem->close($this->resource);
            }

            $this->resource = null;
        }
    }

    protected function store(bool $force = false): void
    {
        $whenRecords = $this->perFile > 0 && $this->records >= $this->perFile;

        if ($force && ! $this->fileCreated) {
            $this->getFile();
        }

        if ($force || $whenRecords) {
            $this->releaseFile();
            $this->records = 0;
        }
    }

    protected function write(Model $model, bool $hasNext): void
    {
        $this->records++;

        $fileCompleted = $this->perFile > 0 && $this->records >= $this->perFile;

        if ($this->writtenFiles === 0 && $fileCompleted && $hasNext) {
            $this->fileIndex = 1;
        }

        $this->append(
            value($this->item, $model, $fileCompleted || ! $hasNext)
        );

        $this->progressBar?->advance();

        if ($fileCompleted) {
            $this->store();
        }
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

        value($this->closeFile, $this->resource, $this->fileIndex, $this->records);

        $this->resource = null;

        $this->writtenFiles++;

        $this->fileIndex++;
    }

    protected function append(string $content): void
    {
        if ($this->records > 1) {
            $content = $this->lineEnding . $content;
        }

        $this->filesystem->append($this->getFile(), $content, $this->feed->path());
    }

    protected function perFile(Feed $feed): int
    {
        $count = $feed->perFile();

        if ($count < 0) {
            throw new InvalidArgumentException("Feed perFile must be greater than or equal to 0, [$count] given.");
        }

        return $count;
    }

    protected function maxFiles(Feed $feed): int
    {
        $count = $feed->maxFiles();

        if ($count < 0) {
            throw new InvalidArgumentException("Feed maxFiles must be greater than or equal to 0, [$count] given.");
        }

        return $count;
    }

    protected function total(): int
    {
        if ($this->capacity === null) {
            return $this->modelCount();
        }

        return min(
            $this->modelCount(),
            $this->capacity
        );
    }

    protected function capacity(): ?int
    {
        if ($this->perFile === 0 || $this->maxFiles === 0) {
            return null;
        }

        if ($this->maxFiles > intdiv(PHP_INT_MAX, $this->perFile)) {
            return PHP_INT_MAX;
        }

        return $this->perFile * $this->maxFiles;
    }

    protected function models(): LazyCollection
    {
        $builder = $this->feed->builder()->applyScopes()->withoutGlobalScopes();
        $query   = $builder->getQuery();

        if ($this->isBounded($query)) {
            return $this->boundedModels($builder, $query);
        }

        return $this->isOrdered($query)
            ? $builder->lazy($this->chunk)
            : $builder->lazyById($this->chunk);
    }

    protected function boundedModels(Builder $builder, QueryBuilder $query): LazyCollection
    {
        $offset    = $this->queryOffset($query);
        $remaining = $this->queryLimit($query);

        if (! $this->isOrdered($query)) {
            $key = empty($query->unions)
                ? $builder->getModel()->getQualifiedKeyName()
                : $builder->getModel()->getKeyName();

            $builder->orderBy($key);
        }

        return LazyCollection::make(function () use ($builder, $offset, $remaining) {
            while ($remaining === null || $remaining > 0) {
                $limit  = $remaining === null ? $this->chunk : min($this->chunk, $remaining);
                $models = (clone $builder)->offset($offset)->limit($limit)->get();
                $count  = $models->count();

                if ($count === 0) {
                    return;
                }

                foreach ($models as $model) {
                    yield $model;
                }

                if ($count < $limit) {
                    return;
                }

                $offset += $count;

                if ($remaining !== null) {
                    $remaining -= $count;
                }
            }
        });
    }

    protected function modelCount(): int
    {
        if ($this->modelCount !== null) {
            return $this->modelCount;
        }

        $builder = $this->feed->builder();
        $query   = $builder->toBase();

        if (! $this->isBounded($query)) {
            return $this->modelCount = $builder->count();
        }

        if ($this->queryLimit($query) === null) {
            $query->limit(PHP_INT_MAX);
        }

        return $this->modelCount = $query->newQuery()->fromSub($query, 'feed_models')->count();
    }

    protected function isBounded(QueryBuilder $query): bool
    {
        return $this->queryLimit($query) !== null || $this->queryOffset($query) > 0;
    }

    protected function isOrdered(QueryBuilder $query): bool
    {
        return ! empty($query->orders) || ! empty($query->unionOrders);
    }

    protected function queryLimit(QueryBuilder $query): ?int
    {
        return empty($query->unions) ? $query->limit : $query->unionLimit;
    }

    protected function queryOffset(QueryBuilder $query): int
    {
        return (int) (empty($query->unions) ? $query->offset : $query->unionOffset);
    }

    protected function createProgressBar(int $total): ?ProgressBar
    {
        return $this->output?->createProgressBar($total);
    }
}
