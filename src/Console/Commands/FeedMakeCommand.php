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

    public function handle(): void
    {
        parent::handle();

        if ($this->option('item')) {
            $this->makeFeedItem(
                $this->argument('name'),
                (bool) $this->option('force')
            );
        }

        if ($this->option('info')) {
            $this->makeFeedInfo(
                $this->argument('name'),
                (bool) $this->option('force')
            );
        }
    }

    protected function makeFeedItem(string $name, bool $force): void
    {
        $this->call(FeedItemMakeCommand::class, [
            'name'    => $name,
            '--force' => $force,
        ]);
    }

    protected function makeFeedInfo(string $name, bool $force): void
    {
        $this->call(FeedInfoMakeCommand::class, [
            'name'    => $name,
            '--force' => $force,
        ]);
    }

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
            ['item', 't', InputOption::VALUE_NONE, 'Create the class with feed item'],
            ['info', 'i', InputOption::VALUE_NONE, 'Create the class with feed info'],
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the feed already exists'],
        ];
    }
}
