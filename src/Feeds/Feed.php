<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Feeds;

use DragonCode\LaravelFeed\Data\ElementData;
use DragonCode\LaravelFeed\Enums\FeedFormatEnum;
use DragonCode\LaravelFeed\Feeds\Info\FeedInfo;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

abstract class Feed
{
    protected FeedFormatEnum $format = FeedFormatEnum::Xml;

    protected string $storage = 'public';

    protected ?string $filename = null;

    public function __construct(
        protected Application $laravel
    ) {}

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

    public function root(): ElementData
    {
        return new ElementData;
    }

    public function info(): FeedInfo
    {
        return new FeedInfo;
    }

    public function filename(): string
    {
        return $this->filename ??= Str::of(static::class)
            ->after($this->laravel->getNamespace() . 'Feeds\\')
            ->ltrim('\\')
            ->replace('\\', ' ')
            ->kebab()
            ->append('.', $this->format->value)
            ->toString();
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
