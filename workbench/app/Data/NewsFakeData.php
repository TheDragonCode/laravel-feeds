<?php

declare(strict_types=1);

namespace Workbench\App\Data;

class NewsFakeData
{
    public static function toArray(): array
    {
        return [
            [
                'title'   => 'Some 1',
                'content' => 'Some content 1',

                'updated_at' => fake()->dateTimeBetween(
                    startDate: static::startDate(),
                    endDate  : static::endDate()
                ),
            ],
            [
                'title'   => 'Some 2',
                'content' => 'Some content 2',

                'updated_at' => fake()->dateTimeBetween(
                    startDate: static::startDate(),
                    endDate  : static::endDate()
                ),
            ],
            [
                'title'   => 'Some 3',
                'content' => 'Some content 3',

                'updated_at' => fake()->dateTimeBetween(
                    startDate: static::startDate(),
                    endDate  : static::endDate()
                ),
            ],
        ];
    }

    protected static function startDate(): string
    {
        return getDefaultDateTime()->subHours(23)->toIso8601String();
    }

    protected static function endDate(): string
    {
        return getDefaultDateTime()->toIso8601String();
    }
}
