<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Concerns;

use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

use function class_basename;
use function str_replace;

/** @mixin \Illuminate\Console\GeneratorCommand */
trait InteractsWithName
{
    protected function getNameInput(): string
    {
        return Str::of(parent::getNameInput())
            ->whenEndsWith('Feed', fn (Stringable $str) => $str->substr(0, -4))
            ->whenEndsWith('FeedItem', fn (Stringable $str) => $str->substr(0, -8))
            ->toString();
    }

    protected function qualifyClass($name): string
    {
        return Str::finish(parent::qualifyClass($name), $this->type);
    }

    protected function buildClass($name): string
    {
        return str_replace(
            ['DummyUser'],
            class_basename($this->userProviderModel()),
            parent::buildClass($name)
        );
    }
}
