<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed;

use DragonCode\LaravelFeed\Console\Commands\FeedGenerateCommand;
use DragonCode\LaravelFeed\Console\Commands\FeedInfoMakeCommand;
use DragonCode\LaravelFeed\Console\Commands\FeedItemMakeCommand;
use DragonCode\LaravelFeed\Console\Commands\FeedMakeCommand;
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
        $this->migrations();
    }

    protected function publishConfig(): void
    {
        $this->publishes([
            __DIR__ . '/../config/feeds.php' => $this->app->configPath('feeds.php'),
        ], ['config', 'feeds']);
    }

    protected function migrations(): void
    {
        $this->publishesMigrations([
            __DIR__ . '/../database/migrations' => $this->app->databasePath('migrations'),
        ], 'feeds');
    }

    protected function registerCommands(): void
    {
        $this->commands([
            FeedGenerateCommand::class,
            FeedInfoMakeCommand::class,
            FeedItemMakeCommand::class,
            FeedMakeCommand::class,
        ]);
    }
}
