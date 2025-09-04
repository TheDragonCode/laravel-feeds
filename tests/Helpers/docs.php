<?php

declare(strict_types=1);

use function Orchestra\Testbench\workbench_path;

function copyFeedFileToDoc(string $source, string $target): void
{
    $sourceFile = workbench_path('app/Feeds/Docs/' . $source . '.php');
    $targetFile = __DIR__ . '/../../docs/snippets/' . $target;

    $content = file_get_contents($sourceFile);

    $content = str_replace([
        'Workbench\App\Models\User',
        'Workbench\App\Feeds\Docs',
    ], [
        'App\Models\User',
        'App\Feeds',
    ], $content);

    file_put_contents($targetFile, $content);
}
