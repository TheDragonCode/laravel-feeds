<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Models;

use DragonCode\LaravelFeed\Casts\ClassCast;
use DragonCode\LaravelFeed\Casts\ExpressionCast;
use DragonCode\LaravelFeed\Casts\SlugCast;
use DragonCode\LaravelFeed\Observers\FeedObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use function config;

#[ObservedBy([FeedObserver::class])]
class Feed extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'slug',
        'class',
        'title',

        'expression',

        'is_active',

        'last_activity',
    ];

    protected $attributes = [
        'expression' => '0 * * * *',

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
            'slug'       => SlugCast::class,

            'is_active' => 'boolean',

            'last_activity' => 'datetime',
        ];
    }
}
