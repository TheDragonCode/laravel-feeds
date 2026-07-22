<?php

declare(strict_types=1);

namespace Tests\Helpers\Benchmark;

use DragonCode\LaravelFeed\Enums\FeedFormatEnum;

final class RegressionThresholds
{
    public static function dataset(): array
    {
        return [
            'xml'        => [FeedFormatEnum::Xml, 20.0],
            'json'       => [FeedFormatEnum::Json, 30.0],
            'json lines' => [FeedFormatEnum::JsonLines, 20.0],
            'csv'        => [FeedFormatEnum::Csv, 20.0],
            'rss'        => [FeedFormatEnum::Rss, 20.0],
        ];
    }

    public static function limits(): array
    {
        $limits = [];

        foreach (self::dataset() as [$format, $limit]) {
            $limits[$format->value] = $limit;
        }

        return $limits;
    }
}
