<?php

declare(strict_types=1);

namespace Workbench\App\Data;

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

                'images' => [
                    'https://via.placeholder.com/640x480.png/008877?text=repudiandae',
                ],

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

                'images' => [
                    'https://via.placeholder.com/640x480.png/009966?text=beatae',
                    'https://via.placeholder.com/640x480.png/000011?text=deleniti',
                    'https://via.placeholder.com/640x480.png/009999?text=voluptates',
                ],

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

                'images' => [
                    'https://via.placeholder.com/640x480.png/000044?text=asperiores',
                    'https://via.placeholder.com/640x480.png/0055ff?text=expedita',
                ],

                'created_at' => '2025-08-29 00:00:00',
                'updated_at' => '2025-08-29 18:00:00',
            ],
        ];
    }
}
