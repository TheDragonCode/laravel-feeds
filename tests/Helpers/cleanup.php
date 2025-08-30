<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;

function deleteFile(string $filename): void
{
    app(Filesystem::class)->delete(
        app_path($filename)
    );
}

function deleteFeed(string $name): void
{
    deleteFile(feedPath($name));
}
