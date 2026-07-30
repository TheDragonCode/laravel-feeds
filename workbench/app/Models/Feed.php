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
        'is_available_fulfilment',
        'is_available_omni',
        'is_available_sfs',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'is_available_fulfilment' => 'boolean',
            'is_available_omni'       => 'boolean',
            'is_available_sfs'        => 'boolean',
        ];
    }
}
