<?php

declare(strict_types=1);

namespace Workbench\App\Data;

use function json_encode;

class ProductFakeData
{
    public static function toArray(): array
    {
        return [
            [
                'article'     => 'GD-PRDCT-1',
                'title'       => 'Some 1',
                'description' => 'Some description 1',

                'price'    => 100,
                'quantity' => 5,
                'currency' => 'USD',

                'brand' => 'The Best',

                'images' => json_encode([
                    'https://via.placeholder.com/640x480.png/00ff55?text=s1',
                ]),

                'created_at' => '2025-08-31 00:00:00',
                'updated_at' => '2025-08-31 20:00:00',
            ],
            [
                'article'     => 'GD-PRDCT-2',
                'title'       => 'Some 2',
                'description' => 'Some description 2',

                'price'    => 250,
                'quantity' => 20,
                'currency' => 'USD',

                'brand' => 'The Best',

                'images' => json_encode([
                    'https://via.placeholder.com/640x480.png/00ff55?text=s20',
                    'https://via.placeholder.com/640x480.png/00ff55?text=s21',
                    'https://via.placeholder.com/640x480.png/00ff55?text=s22',
                ]),

                'created_at' => '2025-08-30 00:00:00',
                'updated_at' => '2025-08-30 19:00:00',
            ],
            [
                'article'     => 'GD-PRDCT-3',
                'title'       => 'Some 3',
                'description' => 'Some description 3',

                'price'    => 400,
                'quantity' => 0,
                'currency' => 'USD',

                'brand' => 'The Best',

                'images' => json_encode([
                    'https://via.placeholder.com/640x480.png/00ff55?text=s30',
                    'https://via.placeholder.com/640x480.png/00ff55?text=s31',
                ]),

                'created_at' => '2025-08-29 00:00:00',
                'updated_at' => '2025-08-29 18:00:00',
            ],
        ];
    }
}
