<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Converters;

use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use DragonCode\LaravelFeed\Services\TransformerService;
use Illuminate\Container\Attributes\Config;

use function implode;
use function is_array;

class CsvConverter extends Converter
{
    public function __construct(
        #[Config('feeds.converters.csv.delimiter')]
        protected string $delimiter,
        TransformerService $transformer
    ) {
        parent::__construct(false, $transformer);
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
        foreach ($data as &$value) {
            if (! is_array($value)) {
                $value = $this->transformValue($value);
            }
        }

        return $data;
    }

    protected function encode(array $data): string
    {
        return implode($this->delimiter, $data);
    }
}
