<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Helpers;

use DragonCode\LaravelFeed\Exceptions\FeedNotFoundException;
use DragonCode\LaravelFeed\Exceptions\UnexpectedFeedException;
use DragonCode\LaravelFeed\Feeds\Feed;
use Illuminate\Foundation\Application;
use Illuminate\Support\Str;

use function class_exists;
use function is_a;

class FeedHelper
{
    public function __construct(
        protected Application $laravel
    ) {}

    public function find(string $class): string
    {
        if (class_exists($class)) {
            return $this->ensure($class);
        }

        if (class_exists($class = $this->resolve($class))) {
            return $this->ensure($class);
        }

        throw new FeedNotFoundException($class);
    }

    protected function resolve(string $class): string
    {
        return Str::of($class)
            ->replace('/', '\\')
            ->ltrim('\\')
            ->start($this->rootNamespace() . 'Feeds\\')
            ->finish('Feed')
            ->toString();
    }

    protected function ensure(string $class): string
    {
        if (! is_a($class, Feed::class, true)) {
            throw new UnexpectedFeedException($class);
        }

        return $class;
    }

    protected function rootNamespace(): string
    {
        return $this->laravel->getNamespace();
    }
}
