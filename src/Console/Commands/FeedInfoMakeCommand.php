<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Console\Commands;

use DragonCode\LaravelFeed\Concerns\InteractsWithName;
use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand('make:feed-info', 'Create a new feed info')]
class FeedInfoMakeCommand extends GeneratorCommand
{
    use InteractsWithName;

    protected $type = 'FeedInfo';

    protected function getStub(): string
    {
        return __DIR__ . '/../../../stubs/feed_info.stub';
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace . '\Feeds\Info';
    }

    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the feed already exists'],
        ];
    }
}
