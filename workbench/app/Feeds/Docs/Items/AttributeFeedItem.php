<?php

declare(strict_types=1);

namespace Workbench\App\Feeds\Docs\Items;

use DragonCode\LaravelFeed\Feeds\Items\FeedItem;

class AttributeFeedItem extends FeedItem
{
    public function attributes(): array
    {
        return [
            'created_at' => $this->model->created_at->toDateTimeString(),
        ];
    }
}
