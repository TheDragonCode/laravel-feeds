<?php

declare(strict_types=1);

namespace Workbench\App\Data;

class ManyFilesData
{
    public static function toArray(): array
    {
        $items = [];

        for ($i = 1; $i <= 5; $i++) {
            $items[] = static::item($i);
        }

        return $items;
    }

    protected static function item(int $number): array
    {
        return [
            'title'    => 'Some ' . $number,
            'content'  => 'Some content ' . $number,
            'category' => 'Some category ' . $number,
        ];
    }
}
