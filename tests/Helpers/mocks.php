<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Console\Commands\FeedMakeCommand;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;
use Illuminate\Support\Facades\ParallelTesting;

function mockOperations(bool $installed = true): void
{
    app()->forgetInstance(FeedMakeCommand::class);

    app()->singleton(FeedMakeCommand::class, function () use ($installed) {
        $mock = mock(Composer::class);
        $mock->shouldReceive('hasPackage')->andReturn($installed);

        return new FeedMakeCommand($mock, new Filesystem);
    });
}

function mockPaths(): void
{
    $token = ParallelTesting::token() ?: '0';

    $operations = config('deploy-operations.path');
    $migrations = database_path($token);

    config()?->set('deploy-operations.path', $operations . '/' . $token);

    app()->useDatabasePath($migrations);
}
