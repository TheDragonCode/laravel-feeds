<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Console\Commands;

use DragonCode\LaravelFeed\Concerns\InteractsWithName;
use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand('make:feed', 'Create a new feed')]
class FeedMakeCommand extends GeneratorCommand
{
    use InteractsWithName;

    protected $type = 'Feed';

    protected function getStub(): string
    {
        return __DIR__ . '/../../../stubs/feed.stub';
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace . '\Feeds';
    }

    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the feed already exists'],
        ];
    }
}
