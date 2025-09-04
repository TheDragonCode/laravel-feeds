<?php

declare(strict_types=1);

namespace Workbench\App\Feeds\Docs;

use DragonCode\LaravelFeed\Data\ElementData;
use DragonCode\LaravelFeed\Feeds\Feed;
use Illuminate\Database\Eloquent\Builder;
use Workbench\App\Models\User;

class RootElementFeed extends Feed
{
    public function builder(): Builder
    {
        return User::query();
    }

    public function root(): ElementData
    {
        return new ElementData(
            name      : 'foo',
            attributes: [
                'count' => $this->builder()->count(),

                'generated_at' => now(),
            ]
        );
    }

    public function filename(): string
    {
        return '../../../../../../../../../docs/snippets/advanced-element-root.xml';
    }
}
