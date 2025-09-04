<?php

declare(strict_types=1);

use function Orchestra\Testbench\workbench_path;

function copyFeedFileToDoc(string $source, string $target, array $replaces = [], bool $cutFilename = true): void
{
    $sourceFile = workbench_path('app/Feeds/Docs/' . $source . '.php');
    $targetFile = __DIR__ . '/../../docs/snippets/' . $target;

    $content = file_get_contents($sourceFile);

    if (! empty($replaces)) {
        $content = str_replace(
            array_keys($replaces),
            array_values($replaces),
            $content
        );
    }

    $content = str_replace([
        'Workbench\App\Feeds\Docs',
        'Workbench\App\\',
    ], [
        'App\Feeds',
        'App\\',
    ], $content);

    if ($cutFilename) {
        $content = preg_replace('/(\n\s+public\sfunction\sfilename\(\):\sstring\n\s+{\n\s+.*\n\s+})/', '', $content);
    }

    file_put_contents($targetFile, $content);
}
