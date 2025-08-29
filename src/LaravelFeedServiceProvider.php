<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed;

use DragonCode\LaravelFeed\Console\Commands\FeedGenerateCommand;
use Illuminate\Support\ServiceProvider;

class LaravelFeedServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/feeds.php', 'feeds');
    }

    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->registerCommands();
        $this->publishConfig();
    }

    protected function publishConfig(): void
    {
        $this->publishes([
            __DIR__ . '/../config/feeds.php' => $this->app->configPath('feeds.php'),
        ], ['config', 'feeds']);
    }

    protected function registerCommands(): void
    {
        $this->commands([
            FeedGenerateCommand::class,
        ]);
    }
}
