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
use DragonCode\LaravelFeed\Feeds\Feed;
use Illuminate\Container\Attributes\Config;
use Illuminate\Container\Container;
use InvalidArgumentException;
use ReflectionClass;

use function array_diff_key;
use function array_keys;
use function implode;
use function sprintf;

class ConverterHelper
{
    public function __construct(
        protected Container $container,
    ) {}

    public function get(FeedFormatEnum $format, array $transformers = []): Converter
    {
        return $this->resolve($format)->prependTransformers($transformers);
    }

    public function forFeed(Feed $feed): Converter
    {
        if (! $options = $feed->converterOptions()) {
            return $this->get($feed->format(), $feed->transformers());
        }

        return $this->resolveWithOptions($feed->format(), $options)->prependTransformers($feed->transformers());
    }

    protected function resolve(FeedFormatEnum $format): Converter
    {
        return $this->resolveWithOptions($format);
    }

    protected function resolveWithOptions(FeedFormatEnum $format, array $options = []): Converter
    {
        $class = match ($format) {
            FeedFormatEnum::Xml       => XmlConverter::class,
            FeedFormatEnum::Json      => JsonConverter::class,
            FeedFormatEnum::JsonLines => JsonLinesConverter::class,
            FeedFormatEnum::Csv       => CsvConverter::class,
            FeedFormatEnum::Rss       => RssConverter::class,
        };

        if ($options) {
            $this->validateOptions($class, $options);
        }

        return $this->container->make($class, $options);
    }

    protected function validateOptions(string $class, array $options): void
    {
        $parameters = [];

        foreach ((new ReflectionClass($class))->getConstructor()->getParameters() as $parameter) {
            if ($parameter->getAttributes(Config::class)) {
                $parameters[$parameter->getName()] = true;
            }
        }

        if ($unknown = array_diff_key($options, $parameters)) {
            throw new InvalidArgumentException(sprintf(
                'Unknown converter options for [%s]: [%s].',
                $class,
                implode(', ', array_keys($unknown)),
            ));
        }
    }
}
