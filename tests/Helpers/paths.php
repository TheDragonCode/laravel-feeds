<?php

declare(strict_types=1);

use Illuminate\Support\Str;

function feedPath(string $name): string
{
    if ($name === Str::upper($name)) {
        $name = Str::lower($name);
    }

    $name = Str::studly($name);

    return app_path('Feeds/' . $name . '.php');
}
