<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Publishers;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

use function class_basename;
use function dirname;
use function file_get_contents;
use function str_replace;
use function vsprintf;

abstract class Publisher
{
    public function __construct(
        protected string $title,
        protected string $class,
        protected Filesystem $filesystem,
    ) {}

    abstract protected function basePath(): string;

    abstract protected function template(): string;

    public function name(): string
    {
        return Str::of(static::class)
            ->classBasename()
            ->before('Publisher')
            ->toString();
    }

    public function publish(): string
    {
        return $this->store(
            $this->path(),
            $this->replace()
        );
    }

    protected function store(string $path, string $contents): string
    {
        $this->filesystem->ensureDirectoryExists(dirname($path));
        $this->filesystem->put($path, $contents);

        return $path;
    }

    protected function replace(): string
    {
        return str_replace(
            ['DummyClass', 'DummyBaseClass', 'DummyTitle'],
            [$this->class, $this->baseClass(), $this->title()],
            $this->load()
        );
    }

    protected function baseClass(): string
    {
        return class_basename($this->class);
    }

    protected function title(): string
    {
        return Str::of($this->title)
            ->replace(['\\', '/'], ': ')
            ->snake(' ')
            ->title()
            ->toString();
    }

    protected function path(): string
    {
        return vsprintf('%s/%s_%s.php', [
            $this->basePath(),
            $this->date(),
            $this->filename(),
        ]);
    }

    protected function filename(): string
    {
        return Str::of($this->title)
            ->snake()
            ->prepend('create_')
            ->append('_feed')
            ->toString();
    }

    protected function date(): string
    {
        return Carbon::now()->format('Y_m_d_His');
    }

    protected function load(): string
    {
        return file_get_contents($this->template());
    }
}
