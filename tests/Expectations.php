<?php

declare(strict_types=1);

expect()->extend('toMatchFileSnapshot', function () {
    $content = file_get_contents($this->value);

    expect($content)->toMatchSnapshot();

    return $this;
});

expect()->extend('toMatchFeedSnapshot', function () {
    $path = feedPath($this->value . 'Feed');

    expect($path)->toMatchFileSnapshot();

    return $this;
});

expect()->extend('toMatchFeedItemSnapshot', function () {
    $path = feedPath('Items/' . $this->value . 'FeedItem');

    expect($path)->toMatchFileSnapshot();

    return $this;
});

expect()->extend('toMatchFeedInfoSnapshot', function () {
    $path = feedPath('Info/' . $this->value . 'FeedInfo');

    expect($path)->toMatchFileSnapshot();

    return $this;
});

expect()->extend('toMatchGeneratedFeed', function () {
    $path = app($this->value->class)->path();

    expect($path)->toBeFile();

    return $this;
});

expect()->pipe('toMatchSnapshot', function (Closure $next) {
    if (! is_string($this->value)) {
        return $this->value;
    }

    $this->value = preg_replace(
        pattern    : '/(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}.\d{6}Z)/',
        replacement: '2025-09-04T04:08:12.000000Z',
        subject    : $this->value
    );

    return $next();
});

expect()->extend('toBeJsonLines', function () {
    foreach (explode("\n", $this->value) as $line) {
        expect($line)->toBeJson();
    }

    return $this;
});

expect()->extend('toBeCsv', function () {
    $rows = parseCsv($this->value);

    expect($rows)->not->toBeEmpty();

    $columns = count($rows[0]);

    foreach ($rows as $row) {
        expect($row)->toHaveCount($columns);
    }

    return $this;
});

function parseCsv(string $content): array
{
    $stream = fopen('php://temp', 'w+b');

    if ($stream === false) {
        throw new RuntimeException('Unable to create a temporary CSV test stream.');
    }

    try {
        $written = fwrite($stream, $content);

        if ($written === false || $written !== strlen($content)) {
            throw new RuntimeException('Unable to write CSV content to the temporary test stream.');
        }

        if (! rewind($stream)) {
            throw new RuntimeException('Unable to rewind the temporary CSV test stream.');
        }

        $rows = [];

        while (($row = fgetcsv(
            $stream,
            null,
            config('feeds.converters.csv.delimiter', ';'),
            config('feeds.converters.csv.enclosure', '"'),
            config('feeds.converters.csv.escape', '')
        )) !== false) {
            $rows[] = $row;
        }

        return $rows;
    } finally {
        fclose($stream);
    }
}

expect()->extend('toBeXml', function () {
    parseXmlDocument($this->value);

    return $this;
});

expect()->extend('toBeRss', function () {
    $document = parseXmlDocument($this->value);

    expect($document->documentElement?->nodeName)->toBe('rss');

    return $this;
});

function parseXmlDocument(string $content): DOMDocument
{
    $document       = new DOMDocument;
    $internalErrors = libxml_use_internal_errors(true);

    try {
        $loaded = $document->loadXML($content, LIBXML_NONET);
        $error  = libxml_get_last_error();
    } finally {
        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);
    }

    if (! $loaded) {
        $reason = $error ? trim($error->message) : 'Unknown parsing error.';

        throw new RuntimeException("Invalid XML: $reason");
    }

    return $document;
}
