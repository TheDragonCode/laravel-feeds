<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Publishers;

use function database_path;

class MigrationPublisher extends Publisher
{
    protected function template(): string
    {
        return __DIR__ . '/../../stubs/migration.stub';
    }

    protected function basePath(): string
    {
        return database_path('migrations');
    }
}
