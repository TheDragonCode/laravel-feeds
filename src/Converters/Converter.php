<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Converters;

use Closure;
use DragonCode\LaravelFeed\Attributes\DateFormat;
use DragonCode\LaravelFeed\Concerns\InteractsWithOptional;
use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use DragonCode\LaravelFeed\Services\TransformerService;
use DragonCode\LaravelFeed\Transformers\DateTimeTransformer;
use Illuminate\Container\Attributes\Config;
use ReflectionClass;

abstract class Converter
{
    use InteractsWithOptional;

    protected array $transformers = [];

    protected array $prependTransformers = [];

    protected ?Closure $transformerPipeline = null;

    protected ?Closure $itemTransformerPipeline = null;

    protected array $itemPipelines = [];

    abstract public function footer(Feed $feed): string;

    abstract public function header(Feed $feed): string;

    abstract public function info(array $info, bool $afterRoot): string;

    abstract public function item(FeedItem $item, bool $isLast): string;

    abstract public function root(Feed $feed): string;

    public function __construct(
        #[Config('feeds.pretty')]
        protected bool $pretty,
        protected readonly TransformerService $transformer,
    ) {}

    public function lineEnding(): string
    {
        return PHP_EOL;
    }

    public function prependTransformers(array $transformers): static
    {
        $this->prependTransformers = $transformers;

        return $this;
    }

    protected function transformValue(mixed $value): bool|float|int|string|null
    {
        return ($this->itemTransformerPipeline ?? $this->pipeline())($value);
    }

    protected function withItem(FeedItem $item, Closure $callback): string
    {
        $previous = $this->itemTransformerPipeline;

        $this->itemTransformerPipeline = $this->itemPipelines[$item::class] ??= $this->itemPipeline($item);

        try {
            return $callback();
        } finally {
            $this->itemTransformerPipeline = $previous;
        }
    }

    protected function itemPipeline(FeedItem $item): Closure
    {
        $class = new ReflectionClass($item);

        do {
            if ($attributes = $class->getAttributes(DateFormat::class)) {
                $transformer = new DateTimeTransformer($attributes[0]->newInstance()->format);
                $pipeline    = $this->pipeline();

                return static fn (mixed $value): bool|float|int|string|null => $pipeline(
                    $transformer->allow($value) ? $transformer->transform($value) : $value
                );
            }
        } while ($class = $class->getParentClass());

        return $this->pipeline();
    }

    protected function pipeline(): Closure
    {
        return $this->transformerPipeline ??= $this->transformer->pipeline(
            $this->transformers,
            $this->prependTransformers
        );
    }
}
