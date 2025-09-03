<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Helpers\ClassExistsHelper;
use Illuminate\Support\Facades\ParallelTesting;

function mockOperations(bool $installed = true): void
{
    app()->forgetInstance(ClassExistsHelper::class);

    app()->singleton(ClassExistsHelper::class, function () use ($installed) {
        $mock = mock(ClassExistsHelper::class);
        $mock->shouldReceive('exists')->andReturn($installed);

        return $mock;
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
