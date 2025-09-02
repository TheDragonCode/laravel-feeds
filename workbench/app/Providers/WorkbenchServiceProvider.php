<?php

declare(strict_types=1);

namespace Workbench\App\Providers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;

use function array_keys;
use function config;

class WorkbenchServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        foreach ($this->disks() as $disk) {
            Storage::fake($disk);
        }
    }

    protected function disks(): array
    {
        return array_keys(config('filesystems.disks'));
    }
}
