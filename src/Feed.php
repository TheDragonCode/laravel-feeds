<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed;

use DragonCode\LaravelFeed\Data\FeedItem;
use DragonCode\LaravelFeed\Data\ModelFeedItem;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

abstract class Feed
{
    protected string $storage = 'public';

    abstract public function builder(): Builder;

    abstract public function filename(): string;

    public function item(Model $model): FeedItem
    {
        return new ModelFeedItem($model);
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function header(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>';
    }

    public function footer(): string
    {
        return '';
    }

    public function path(): string
    {
        return $this->storage()->path(
            $this->filename()
        );
    }

    public function storage(): Filesystem
    {
        return Storage::disk($this->storage);
    }
}
