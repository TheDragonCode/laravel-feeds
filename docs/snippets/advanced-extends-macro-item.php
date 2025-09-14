<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use Illuminate\Support\ServiceProvider;

class FeedServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        FeedItem::macro('titleWithPrefix', function () {
            return sprintf('[%s]: %s]', $this->model->getKey(), $this->model->title);
        });
    }
}

class ProductFeedItem extends FeedItem
{
    public function toArray(): array
    {
        return [
            'title' => $this->titleWithPrefix(),
        ];
    }
}
