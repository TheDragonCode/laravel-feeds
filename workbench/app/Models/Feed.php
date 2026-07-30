<?php

declare(strict_types=1);

namespace Workbench\App\Models;

use DragonCode\LaravelFeed\Models\Feed as BaseFeed;

class Feed extends BaseFeed
{
    protected $fillable = [
        'class',
        'title',
        'expression',
        'is_active',
        'last_activity',
        'is_foo',
        'is_bar',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'is_foo' => 'boolean',
            'is_bar' => 'boolean',
        ];
    }
}
