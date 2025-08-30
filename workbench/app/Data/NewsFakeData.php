<?php

declare(strict_types=1);

namespace Workbench\App\Data;

class NewsFakeData
{
    public static function toArray(): array
    {
        return [
            [
                'title'      => 'Some 1',
                'content'    => 'Some content 1',
                'updated_at' => fake()->dateTimeBetween(startDate: '-23 hours'),
            ],
            [
                'title'      => 'Some 2',
                'content'    => 'Some content 2',
                'updated_at' => fake()->dateTimeBetween(startDate: '-23 hours'),
            ],
            [
                'title'      => 'Some 3',
                'content'    => 'Some content 3',
                'updated_at' => fake()->dateTimeBetween(startDate: '-23 hours'),
            ],
        ];
    }
}
