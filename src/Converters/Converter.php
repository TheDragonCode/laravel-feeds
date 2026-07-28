<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Converters;

use Closure;
use DragonCode\LaravelFeed\Concerns\InteractsWithOptional;
use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use DragonCode\LaravelFeed\Services\TransformerService;
use Illuminate\Container\Attributes\Config;

abstract class Converter
{
    use InteractsWithOptional;

    protected array $transformers = [];

    protected ?Closure $transformerPipeline = null;

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

    public function withLocalTransformers(array $transformers): static
    {
        $this->transformers = $transformers + $this->transformers;

        return $this;
    }

    protected function transformValue(mixed $value): bool|float|int|string|null
    {
        return ($this->transformerPipeline ??= $this->transformer->pipeline($this->transformers))($value);
    }
}
