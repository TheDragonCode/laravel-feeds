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
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;

use function class_basename;

abstract class Feed
{
    use Conditionable;
    use Macroable;

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
        return match ($this->format()) {
            FeedFormatEnum::Xml,
            FeedFormatEnum::Rss => '<?xml version="1.0" encoding="UTF-8"?>',
            default             => ''
        };
    }

    public function footer(): string
    {
        return '';
    }

    public function root(): ElementData
    {
        return new ElementData(
            name: Str::of(static::class)
                ->classBasename()
                ->beforeLast(class_basename(self::class))
                ->snake()
                ->toString()
        );
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
            ->append('.', $this->format()->value)
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

    public function format(): FeedFormatEnum
    {
        return $this->format;
    }
}
