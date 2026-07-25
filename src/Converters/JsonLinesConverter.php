<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Converters;

use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use DragonCode\LaravelFeed\Services\TransformerService;
use Illuminate\Container\Attributes\Config;

use function array_is_list;
use function array_values;
use function is_array;
use function json_encode;

class JsonLinesConverter extends Converter
{
    public function __construct(
        #[Config('feeds.converters.jsonl.options')]
        protected int $options,
        TransformerService $transformer
    ) {
        parent::__construct(false, $transformer);

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

        return $this->encode($data);
    }

    public function info(array $info, bool $afterRoot): string
    {
        $data = $this->performItem($info);

        return $this->encode($data);
    }

    protected function performItem(array $data): array
    {
        $isList = array_is_list($data);

        foreach ($data as $key => &$value) {
            if ($this->isOptional($value)) {
                unset($data[$key]);

                continue;
            }

            if (is_array($value)) {
                $value = $this->performItem($value);

                continue;
            }

            $value = $this->transformValue($value);
        }

        unset($value);

        return $isList ? array_values($data) : $data;
    }

    protected function encode(array $data): string
    {
        return json_encode($data, $this->options);
    }
}
