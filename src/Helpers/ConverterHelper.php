<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Helpers;

use DragonCode\LaravelFeed\Converters\Converter;
use DragonCode\LaravelFeed\Converters\CsvConverter;
use DragonCode\LaravelFeed\Converters\JsonConverter;
use DragonCode\LaravelFeed\Converters\JsonLinesConverter;
use DragonCode\LaravelFeed\Converters\RssConverter;
use DragonCode\LaravelFeed\Converters\XmlConverter;
use DragonCode\LaravelFeed\Enums\FeedFormatEnum;
use Illuminate\Container\Container as LaravelContainer;
use Illuminate\Contracts\Container\Container;

class ConverterHelper
{
    protected Container $container;

    public function __construct(
        ?Container $container = null,
    ) {
        $this->container = $container ?? LaravelContainer::getInstance();
    }

    public function get(FeedFormatEnum $format): Converter
    {
        return $this->container->make(
            match ($format) {
                FeedFormatEnum::Xml       => XmlConverter::class,
                FeedFormatEnum::Json      => JsonConverter::class,
                FeedFormatEnum::JsonLines => JsonLinesConverter::class,
                FeedFormatEnum::Csv       => CsvConverter::class,
                FeedFormatEnum::Rss       => RssConverter::class,
            }
        );
    }
}
