<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Publishers;

use function config;

class OperationPublisher extends Publisher
{
    protected function template(): string
    {
        return __DIR__ . '/../../stubs/operation.stub';
    }

    protected function basePath(): string
    {
        return config('deploy-operations.path');
    }
}
