<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\ParallelTesting;

function deleteOperations(): void
{
    (new Filesystem)->deleteDirectory(
        config('deploy-operations.path')
    );
}

function deleteMigrations(): void
{
    $token = ParallelTesting::token() ?: '0';

    (new Filesystem)->deleteDirectory(
        database_path($token)
    );
}
