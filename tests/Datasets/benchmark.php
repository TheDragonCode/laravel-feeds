<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Enums\FeedFormatEnum;

dataset('feed generation regression formats', [
    'xml'        => [FeedFormatEnum::Xml, 20.0],
    'json'       => [FeedFormatEnum::Json, 30.0],
    'json lines' => [FeedFormatEnum::JsonLines, 20.0],
    'csv'        => [FeedFormatEnum::Csv, 20.0],
    'rss'        => [FeedFormatEnum::Rss, 20.0],
]);
