<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Converters;

use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use DragonCode\LaravelFeed\Services\TransformerService;
use Illuminate\Container\Attributes\Config;

abstract class Converter
{
    protected array $transformers = [];

    public function __construct(
        #[Config('feeds.pretty')]
        protected bool $pretty,
        protected readonly TransformerService $transformer,
    ) {}

    abstract public function header(Feed $feed): string;

    abstract public function footer(Feed $feed): string;

    abstract public function root(Feed $feed): string;

    abstract public function item(FeedItem $item, bool $isLast): string;

    abstract public function info(array $info, bool $afterRoot): string;

    protected function transformValue(mixed $value): bool|float|int|string|null
    {
        return $this->transformer->transform($value, $this->transformers);
    }
}
