<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Converters\CsvConverter;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;

final class ControlledCsvConverter extends CsvConverter
{
    public string $failure;

    protected function openStream(): mixed
    {
        return $this->failure === 'open' ? false : parent::openStream();
    }

    protected function writeRow(mixed $stream, array $data): false|int
    {
        return $this->failure === 'write' ? false : parent::writeRow($stream, $data);
    }

    protected function rewindStream(mixed $stream): bool
    {
        return $this->failure === 'rewind' ? false : parent::rewindStream($stream);
    }

    protected function readStream(mixed $stream): false|string
    {
        return $this->failure === 'read' ? false : parent::readStream($stream);
    }
}

test('reports temporary CSV stream failures', function (string $failure, string $message) {
    $converter          = app(ControlledCsvConverter::class);
    $converter->failure = $failure;

    $item = mock(FeedItem::class);
    $item->shouldReceive('toArray')->once()->andReturn(['id' => 1]);

    expect(fn () => $converter->item($item, true))
        ->toThrow(RuntimeException::class, $message);
})->with([
    'open'   => ['open', 'Unable to create a temporary CSV stream.'],
    'write'  => ['write', 'Unable to encode the CSV row.'],
    'rewind' => ['rewind', 'Unable to rewind the temporary CSV stream.'],
    'read'   => ['read', 'Unable to read the encoded CSV row.'],
]);
