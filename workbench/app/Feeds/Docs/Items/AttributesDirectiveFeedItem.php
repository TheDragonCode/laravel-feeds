<?php

declare(strict_types=1);

namespace Workbench\App\Feeds\Docs\Items;

use DragonCode\LaravelFeed\Feeds\Items\FeedItem;

class AttributesDirectiveFeedItem extends FeedItem
{
    public function toArray(): array
    {
        return [
            'name' => $this->model->name,

            'contact' => [
                '@attributes' => [
                    'email' => $this->model->email,
                    'phone' => '555-000-' . $this->model->id,
                ],
            ],
        ];
    }
}
