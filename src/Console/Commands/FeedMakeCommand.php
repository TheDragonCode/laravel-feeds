<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Console\Commands;

use DragonCode\LaravelDeployOperations\Operation;
use DragonCode\LaravelFeed\Concerns\InteractsWithName;
use DragonCode\LaravelFeed\Helpers\ClassExistsHelper;
use DragonCode\LaravelFeed\Publishers\MigrationPublisher;
use DragonCode\LaravelFeed\Publishers\OperationPublisher;
use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

use function app;
use function vsprintf;

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

        $this->makeOperation(
            $this->argument('name'),
            $this->getQualifyClass()
        );
    }

    protected function makeOperation(string $name, string $class): void
    {
        $publisher = $this->hasOperations()
            ? app(OperationPublisher::class, ['title' => $name, 'class' => $class])
            : app(MigrationPublisher::class, ['title' => $name, 'class' => $class]);

        $this->components->info(vsprintf('%s [%s] created successfully.', [
            $publisher->name(),
            $publisher->publish(),
        ]));
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

    protected function getQualifyClass(): string
    {
        return $this->qualifyClass($this->getNameInput());
    }

    protected function hasOperations(): bool
    {
        return app(ClassExistsHelper::class)->exists(Operation::class);
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
