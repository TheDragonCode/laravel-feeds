<?php

declare(strict_types=1);

namespace Workbench\App\Feeds\Items;

use DragonCode\LaravelFeed\Feeds\Items\FeedItem;

/** @property-read \Workbench\App\Models\Product $model */
class SitemapFeedItem extends FeedItem
{
    public function name(): string
    {
        return 'url';
    }

    public function toArray(): array
    {
        return [
            'loc' => $this->model->url,

            'lastmod' => $this->model->updated_at,

            'priority' => 0.9,
        ];
    }
}
