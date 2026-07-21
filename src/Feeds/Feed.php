<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Feeds;

use DragonCode\LaravelFeed\Data\ElementData;
use DragonCode\LaravelFeed\Enums\FeedFormatEnum;
use DragonCode\LaravelFeed\Exceptions\UnsupportedStorageDiskException;
use DragonCode\LaravelFeed\Feeds\Info\FeedInfo;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;

use function class_basename;
use function get_debug_type;

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

    public function perFile(): int
    {
        return 0;
    }

    public function maxFiles(): int
    {
        return 0;
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

    public function path(int|string $suffix = ''): string
    {
        return $this->storage()->path(
            $this->storagePath($suffix)
        );
    }

    public function storagePath(int|string $suffix = ''): string
    {
        if (empty($suffix)) {
            return $this->filename();
        }

        $filename = $this->filename();

        $directory = pathinfo($filename, PATHINFO_DIRNAME);
        $basename  = pathinfo($filename, PATHINFO_FILENAME);
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        if ($suffix) {
            $suffix = '-' . $suffix;
        }

        $filename = "$basename$suffix.$extension";

        return $directory === '.' ? $filename : "$directory/$filename";
    }

    public function storage(): FilesystemAdapter
    {
        $storage = Storage::disk($this->storage);

        if (! $storage instanceof FilesystemAdapter) {
            throw new UnsupportedStorageDiskException($this->storage, get_debug_type($storage));
        }

        return $storage;
    }

    public function format(): FeedFormatEnum
    {
        return $this->format;
    }
}
