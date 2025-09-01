<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;

function deleteFile(string $filename): void
{
    app(Filesystem::class)->delete(
        $filename
    );
}

function deleteFeed(string $feedName): void
{
    $path = app_path(
        feedPath($feedName)
    );

    deleteFile($path);
}

function deleteFeedResult(string $feedClass): void
{
    deleteFile(
        app($feedClass)->path()
    );
}
