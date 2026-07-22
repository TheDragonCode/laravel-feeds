<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Enums\FeedFormatEnum;

dataset('feed generation regression formats', [
    'xml'        => [FeedFormatEnum::Xml, 25.0],
    'json'       => [FeedFormatEnum::Json, 35.0],
    'json lines' => [FeedFormatEnum::JsonLines, 25.0],
    'csv'        => [FeedFormatEnum::Csv, 25.0],
    'rss'        => [FeedFormatEnum::Rss, 25.0],
]);
