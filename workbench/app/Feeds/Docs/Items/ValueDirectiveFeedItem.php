<?php

declare(strict_types=1);

namespace Workbench\App\Feeds\Docs\Items;

use DragonCode\LaravelFeed\Feeds\Items\FeedItem;

class ValueDirectiveFeedItem extends FeedItem
{
    public function toArray(): array
    {
        return [
            'name' => [
                '@value' => $this->model->name,
            ],

            'contact' => [
                '@attributes' => ['type' => 'email'],
                '@value'      => $this->model->email,
            ],
        ];
    }
}
