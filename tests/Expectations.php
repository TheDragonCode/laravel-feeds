<?php

declare(strict_types=1);

expect()->extend('toMatchFileSnapshot', function () {
    $content = readFeedFile($this->value);

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

expect()->extend('toBeJsonDocument', function () {
    expect(parseJsonDocument($this->value))->toBeArray();

    return $this;
});

expect()->extend('toBeJsonLines', function () {
    expect(parseJsonLines($this->value))->not->toBeEmpty();

    return $this;
});

expect()->extend('toBeCsv', function (?array $expectedRows = null) {
    $rows = parseCsv($this->value);

    if ($rows === []) {
        throw new RuntimeException('Invalid CSV: expected at least one row.');
    }

    $columns = count($rows[0]);

    foreach ($rows as $index => $row) {
        $actualColumns = count($row);

        if ($actualColumns !== $columns) {
            $line = $index + 1;

            throw new RuntimeException(
                "Invalid CSV row at line [$line]: expected [$columns] columns, got [$actualColumns]."
            );
        }

        if ($row === [null]) {
            $line = $index + 1;

            throw new RuntimeException("Invalid CSV row at line [$line]: empty records are not allowed.");
        }
    }

    if ($expectedRows !== null) {
        expect($rows)->toBe($expectedRows);
    } else {
        expect($rows)->toBeArray();
    }

    return $this;
});

function readFeedFile(string $path): string
{
    if (! is_file($path) || ! is_readable($path)) {
        throw new RuntimeException("Unable to read generated feed file: [$path].");
    }

    $content = file_get_contents($path);

    if ($content === false) {
        throw new RuntimeException("Unable to read generated feed file: [$path].");
    }

    return $content;
}

function parseJsonDocument(string $content): array
{
    $value = decodeJsonFeed($content, 'JSON document');

    if (! is_array($value)) {
        throw new RuntimeException('Invalid JSON document: expected an object or array.');
    }

    return $value;
}

function parseJsonLines(string $content): array
{
    if ($content === '') {
        throw new RuntimeException('Invalid JSON Lines: expected at least one record.');
    }

    $normalized = str_replace(["\r\n", "\r"], "\n", $content);
    $lines      = explode("\n", $normalized);

    if (str_ends_with($normalized, "\n")) {
        array_pop($lines);
    }

    $records = [];

    foreach ($lines as $index => $line) {
        $number = $index + 1;
        $value  = decodeJsonFeed($line, "JSON Lines record at line [$number]");

        if (! is_array($value)) {
            throw new RuntimeException(
                "Invalid JSON Lines record at line [$number]: expected an object or array."
            );
        }

        $records[] = $value;
    }

    return $records;
}

function decodeJsonFeed(string $content, string $context): mixed
{
    try {
        return json_decode(
            json       : $content,
            associative: true,
            flags      : JSON_THROW_ON_ERROR
        );
    } catch (JsonException $exception) {
        throw new RuntimeException(
            "Invalid $context: {$exception->getMessage()}",
            previous: $exception
        );
    }
}

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
    $document = parseXmlDocument($this->value);

    expect($document->documentElement)->toBeInstanceOf(DOMElement::class);

    return $this;
});

expect()->extend('toBeRss', function () {
    $document = parseXmlDocument($this->value);
    $root     = $document->documentElement;

    if ($root?->nodeName !== 'rss') {
        throw new RuntimeException('Invalid RSS: expected the root element [rss].');
    }

    if ($root->getAttribute('version') !== '2.0') {
        throw new RuntimeException('Invalid RSS: expected version [2.0].');
    }

    $channels = 0;

    foreach ($root->childNodes as $node) {
        if ($node instanceof DOMElement && $node->nodeName === 'channel') {
            $channels++;
        }
    }

    if ($channels !== 1) {
        throw new RuntimeException('Invalid RSS: expected exactly one [channel] element.');
    }

    expect($root)->toBeInstanceOf(DOMElement::class);

    return $this;
});

function parseXmlDocument(string $content): DOMDocument
{
    $document       = new DOMDocument;
    $internalErrors = libxml_use_internal_errors(true);

    libxml_clear_errors();

    try {
        $loaded = $document->loadXML($content, LIBXML_NONET);
        $errors = libxml_get_errors();
    } catch (Throwable $exception) {
        throw new RuntimeException(
            "Invalid XML: {$exception->getMessage()}",
            previous: $exception
        );
    } finally {
        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);
    }

    if (! $loaded || $errors !== []) {
        $messages = [];

        foreach ($errors as $error) {
            $messages[] = trim($error->message);
        }

        $reason = $messages === [] ? 'Unknown parsing error.' : implode('; ', $messages);

        throw new RuntimeException("Invalid XML: $reason");
    }

    return $document;
}
