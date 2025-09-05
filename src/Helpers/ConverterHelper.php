<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Helpers;

use DragonCode\LaravelFeed\Converters\Converter;
use DragonCode\LaravelFeed\Converters\JsonConverter;
use DragonCode\LaravelFeed\Converters\JsonLinesConverter;
use DragonCode\LaravelFeed\Converters\XmlConverter;
use DragonCode\LaravelFeed\Enums\FeedFormatEnum;

use function app;

class ConverterHelper
{
    public function get(FeedFormatEnum $format): Converter
    {
        return match ($format) {
            FeedFormatEnum::Xml       => app(XmlConverter::class),
            FeedFormatEnum::Json      => app(JsonConverter::class),
            FeedFormatEnum::JsonLines => app(JsonLinesConverter::class),
        };
    }
}
