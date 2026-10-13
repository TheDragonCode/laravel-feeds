<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Models;

use DragonCode\LaravelFeed\Casts\ClassCast;
use DragonCode\LaravelFeed\Casts\ExpressionCast;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

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

    protected function title(): Attribute
    {
        return Attribute::make(set: static fn (string $value): array => [
            'title' => $value,
            'slug'  => Str::slug($value),
        ]);
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
