<?php

declare(strict_types=1);

namespace Workbench\App\Providers;

use Laravel\Boost\BoostServiceProvider as ServiceProvider;
use Laravel\Roster\Roster;

class BoostServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->forgetInstance(Roster::class);

        $this->app->singleton(Roster::class, function () {
            $lockFiles = [
                $this->basePath('composer.lock'),
                $this->basePath('package-lock.json'),
                $this->basePath('bun.lockb'),
                $this->basePath('pnpm-lock.yaml'),
                $this->basePath('yarn.lock'),
            ];

            $cacheKey     = 'boost.roster.scan';
            $lastModified = max(array_map(fn ($path) => file_exists($path) ? filemtime($path) : 0, $lockFiles));

            $cached = cache()->get($cacheKey);
            if ($cached && isset($cached['timestamp']) && $cached['timestamp'] >= $lastModified) {
                return $cached['roster'];
            }

            $roster = Roster::scan($this->basePath());
            cache()->put($cacheKey, [
                'roster'    => $roster,
                'timestamp' => time(),
            ], now()->addHours(24));

            return $roster;
        });
    }

    protected function basePath(string $filename = ''): string
    {
        return __DIR__ . '/../../../' . $filename;
    }
}
