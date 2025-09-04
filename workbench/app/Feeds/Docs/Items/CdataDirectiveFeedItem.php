<?php

declare(strict_types=1);

namespace Workbench\App\Feeds\Docs\Items;

use DragonCode\LaravelFeed\Feeds\Items\FeedItem;

use function sprintf;

class CdataDirectiveFeedItem extends FeedItem
{
    public function toArray(): array
    {
        return [
            'name' => [
                '@cdata' => sprintf('<h1>%s</h1>', $this->model->name),
            ],

            'email' => $this->model->email,
        ];
    }
}
