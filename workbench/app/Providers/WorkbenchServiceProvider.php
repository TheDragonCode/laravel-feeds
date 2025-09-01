<?php

declare(strict_types=1);

namespace Workbench\App\Providers;

use Illuminate\Config\Repository;
use Illuminate\Foundation\Application;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class WorkbenchServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->isFreshLaravel()) {
            return;
        }

        Repository::macro('collection', function (string $key) {
            return new Collection($this->get($key));
        });
    }

    protected function isFreshLaravel(): bool
    {
        return Str::of(Application::VERSION)->before('.')->toString() === '12';
    }
}
