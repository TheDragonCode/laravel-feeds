<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Models;

use DragonCode\LaravelFeed\Casts\ExpressionCast;
use DragonCode\LaravelFeed\Enums\FeedFormatEnum;
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
        'format',

        'is_active',

        'last_activity',
    ];

    protected $attributes = [
        'expression' => '* * * * *',

        'format' => FeedFormatEnum::Xml,

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
            'expression' => ExpressionCast::class,
            'format'     => FeedFormatEnum::class,

            'is_active' => 'boolean',

            'last_activity' => 'datetime',
        ];
    }
}
