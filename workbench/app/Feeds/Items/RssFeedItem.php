<?php

declare(strict_types=1);

namespace Workbench\App\Feeds\Items;

use DragonCode\LaravelFeed\Feeds\Items\FeedItem;

/** @property-read \Workbench\App\Models\News $model */
class RssFeedItem extends FeedItem
{
    public function toArray(): array
    {
        return [
            'title' => $this->model->title,

            'link' => $this->model->url,
            'guid' => $this->model->url,

            'description' => [
                '@cdata' => $this->model->content,
            ],

            'category' => $this->model->category,

            'pubDate' => $this->model->created_at->toRfc1123String(),
        ];
    }
}
