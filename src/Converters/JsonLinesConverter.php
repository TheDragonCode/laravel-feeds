<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Converters;

use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use DragonCode\LaravelFeed\Services\TransformerService;
use Illuminate\Container\Attributes\Config;

use function is_array;
use function json_encode;

class JsonLinesConverter extends Converter
{
    public function __construct(
        #[Config('feeds.converters.json.options')]
        protected int $options,
        #[Config('feeds.pretty')]
        bool $pretty,
        TransformerService $transformer
    ) {
        parent::__construct($pretty, $transformer);

        $this->options &= ~JSON_PRETTY_PRINT;
    }

    public function header(Feed $feed): string
    {
        return '';
    }

    public function footer(Feed $feed): string
    {
        return '';
    }

    public function root(Feed $feed): string
    {
        return '';
    }

    public function item(FeedItem $item, bool $isLast): string
    {
        $data = $this->performItem($item->toArray());

        return $this->toJson($data);
    }

    public function info(array $info, bool $afterRoot): string
    {
        $data = $this->performItem($info);

        return $this->toJson($data);
    }

    protected function performItem(array $data): array
    {
        foreach ($data as &$value) {
            if (is_array($value)) {
                $value = $this->performItem($value);

                continue;
            }

            $value = $this->transformValue($value);
        }

        return $data;
    }

    protected function toJson(array $data): string
    {
        return json_encode($data, $this->options);
    }
}
