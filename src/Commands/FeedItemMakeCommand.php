<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Commands;

use DragonCode\LaravelFeed\Concerns\InteractsWithName;
use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand('make:feed-item', 'Create a new feed item')]
class FeedItemMakeCommand extends GeneratorCommand
{
    use InteractsWithName;

    protected $type = 'FeedItem';

    protected function getStub(): string
    {
        return __DIR__ . '/../../stubs/feed_item.stub';
    }

    protected function getDefaultNamespace($rootNamespace): string // @pest-ignore-type
    {
        return $rootNamespace . '\Feeds\Items';
    }

    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the feed already exists'],
        ];
    }
}
