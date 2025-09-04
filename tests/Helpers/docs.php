<?php

declare(strict_types=1);

function copyFeedFileToDoc(string $source, string $target, bool $removeFilename = true): void
{
    $sourceFile = __DIR__ . '/../../workbench/app/Feeds/Docs/' . $source . '.php';
    $targetFile = __DIR__ . '/../../docs/snippets/' . $target;

    $content = file_get_contents($sourceFile);

    $content = str_replace([
        'Workbench\App\Models\User',
        'Workbench\App\Feeds\Docs',
    ], [
        'App\Models\User',
        'App\Feeds',
    ], $content);

    if ($removeFilename) {
        $content = preg_replace('/(\n\s+public\sfunction\sfilename\(\):\sstring\n\s+{\n\s+.*\n\s+})/', '', $content);
    }

    file_put_contents($targetFile, $content);
}
