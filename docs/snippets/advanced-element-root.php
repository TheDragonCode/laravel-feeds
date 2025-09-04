<?php

declare(strict_types=1);

namespace App\Feeds;

use DragonCode\LaravelFeed\Data\ElementData;
use DragonCode\LaravelFeed\Feeds\Feed;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;

use function now;

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
}
