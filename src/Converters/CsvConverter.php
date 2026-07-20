<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Converters;

use DragonCode\LaravelFeed\Exceptions\InvalidCsvRowException;
use DragonCode\LaravelFeed\Exceptions\InvalidCsvValueException;
use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use DragonCode\LaravelFeed\Services\TransformerService;
use Illuminate\Container\Attributes\Config;
use RuntimeException;

use function array_key_exists;
use function array_keys;
use function array_values;
use function count;
use function fclose;
use function fopen;
use function fputcsv;
use function is_array;
use function rewind;
use function stream_get_contents;

class CsvConverter extends Converter
{
    protected ?array $columns = null;

    public function __construct(
        #[Config('feeds.converters.csv.delimiter', ';')]
        protected string $delimiter,
        TransformerService $transformer,
        #[Config('feeds.converters.csv.enclosure', '"')]
        protected string $enclosure = '"',
        #[Config('feeds.converters.csv.escape', '')]
        protected string $escape = '',
        #[Config('feeds.converters.csv.line_ending', PHP_EOL)]
        protected string $lineEnding = PHP_EOL,
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
        $data = $this->transform($info);

        return $this->encode($data);
    }

    public function lineEnding(): string
    {
        return $this->lineEnding;
    }

    protected function performItem(array $data): array
    {
        return $this->transform(
            $this->order($data)
        );
    }

    protected function transform(array $data): array
    {
        foreach ($data as $column => &$value) {
            if (is_array($value)) {
                throw new InvalidCsvValueException($column);
            }

            $value = $this->transformValue($value);
        }

        unset($value);

        return array_values($data);
    }

    protected function encode(array $data): string
    {
        $stream = fopen('php://temp', 'w+b');

        if ($stream === false) {
            throw new RuntimeException('Unable to create a temporary CSV stream.');
        }

        try {
            if (fputcsv($stream, $data, $this->delimiter, $this->enclosure, $this->escape, '') === false) {
                throw new RuntimeException('Unable to encode the CSV row.');
            }

            if (! rewind($stream)) {
                throw new RuntimeException('Unable to rewind the temporary CSV stream.');
            }

            $content = stream_get_contents($stream);

            if ($content === false) {
                throw new RuntimeException('Unable to read the encoded CSV row.');
            }

            return $content;
        } finally {
            fclose($stream);
        }
    }

    protected function order(array $data): array
    {
        $actual = array_keys($data);

        if ($this->columns === null) {
            $this->columns = $actual;

            return $data;
        }

        if (count($data) !== count($this->columns)) {
            throw new InvalidCsvRowException($this->columns, $actual);
        }

        $ordered = [];

        foreach ($this->columns as $column) {
            if (! array_key_exists($column, $data)) {
                throw new InvalidCsvRowException($this->columns, $actual);
            }

            $ordered[$column] = $data[$column];
        }

        return $ordered;
    }
}
