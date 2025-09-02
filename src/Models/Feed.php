<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Models;

use DragonCode\LaravelFeed\Casts\ClassCast;
use DragonCode\LaravelFeed\Casts\ExpressionCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use function config;

class Feed extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'class',
        'title',

        'expression',

        'is_active',

        'last_activity',
    ];

    protected $attributes = [
        'expression' => '* * * * *',

        'is_active' => true,
    ];

    public function getConnectionName(): ?string
    {
        return config('feeds.table.connection');
    }

    public function getTable(): ?string
    {
        return config('feeds.table.table') ?? parent::getTable();
    }

    protected function casts(): array
    {
        return [
            'class'      => ClassCast::class,
            'expression' => ExpressionCast::class,

            'is_active' => 'boolean',

            'last_activity' => 'datetime',
        ];
    }
}
