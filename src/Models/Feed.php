<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Models;

use DragonCode\LaravelFeed\Casts\ClassCast;
use DragonCode\LaravelFeed\Casts\ExpressionCast;
use DragonCode\LaravelFeed\Casts\SlugCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use function config;
use function in_array;

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

    protected function fireModelEvent($event, $halt = true)
    {
        $result = parent::fireModelEvent($event, $halt);

        if ($result !== false && in_array($event, ['saving', 'creating', 'updating'], true)) {
            $attributes = $this->getAttributes();

            if (isset($attributes['title'])) {
                $this->slug = $attributes['title'];
            }
        }

        return $result;
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
