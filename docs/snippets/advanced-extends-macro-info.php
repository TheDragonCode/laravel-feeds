<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Feeds\Info\FeedInfo;
use Illuminate\Support\ServiceProvider;

class FeedServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        FeedInfo::macro('titleWithPrefix', function () {
            return sprintf('[%s]: %s', date('Y'), $this->title);
        });
    }
}

class ProductFeedInfo extends FeedInfo
{
    public function __construct(
        protected string $title,
    ) {}

    public function toArray(): array
    {
        return [
            'title' => $this->titleWithPrefix(),
        ];
    }
}
