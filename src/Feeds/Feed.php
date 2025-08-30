<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Feeds;

use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use function class_basename;

abstract class Feed
{
    protected string $storage = 'public';

    protected ?string $filename = null;

    abstract public function builder(): Builder;

    public function item(Model $model): FeedItem
    {
        return new FeedItem($model);
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

    public function rootItem(): ?string
    {
        return null;
    }

    public function filename(): string
    {
        return $this->filename ??= Str::kebab(class_basename($this)) . '.xml';
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
