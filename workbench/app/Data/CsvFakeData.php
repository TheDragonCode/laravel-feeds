<?php

declare(strict_types=1);

namespace Workbench\App\Data;

use function getDefaultDateTime;

class CsvFakeData
{
    public static function toArray(): array
    {
        return [
            [
                'title'   => 'Товар; "один"',
                'content' => "Первая строка\r\nВторая строка",

                'category' => 'Новости UTF-8',

                'updated_at' => getDefaultDateTime(),
            ],
            [
                'title'   => 'Some 2',
                'content' => '',

                'category' => '',

                'updated_at' => getDefaultDateTime(),
            ],
            [
                'title'   => 'Some 3',
                'content' => 'Quote " and delimiter ;',

                'category' => 'Категория 3',

                'updated_at' => null,
            ],
        ];
    }
}
