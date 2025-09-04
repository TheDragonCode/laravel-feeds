<?php

declare(strict_types=1);

namespace Workbench\App\Feeds\Docs\Items;

use DragonCode\LaravelFeed\Feeds\Items\FeedItem;

class MixedDirectiveFeedItem extends FeedItem
{
    public function toArray(): array
    {
        return [
            'name' => $this->model->name,

            '@mixed' => <<<XML
                <some>
                    <first>Foo</first>
                    <second>{$this->model->email}</second>
                </some>
                XML,
        ];
    }
}
