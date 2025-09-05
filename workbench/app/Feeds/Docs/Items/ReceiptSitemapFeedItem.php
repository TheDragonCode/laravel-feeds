<?php

declare(strict_types=1);

namespace Workbench\App\Feeds\Docs\Items;

use DragonCode\LaravelFeed\Feeds\Items\FeedItem;

use function route;

/** @property-read \Workbench\App\Models\Product $model */
class ReceiptSitemapFeedItem extends FeedItem
{
    public function name(): string
    {
        return 'url';
    }

    public function toArray(): array
    {
        return [
            'loc' => route('products.show', $this->model->slug),

            'lastmod' => $this->model->updated_at,

            'priority' => 0.9,
        ];
    }
}
