<?php

declare(strict_types=1);

use Spatie\TemporaryDirectory\TemporaryDirectory;

test('accepts valid format fixtures containing special characters', function (
    string $expectation,
    string $content,
    array $arguments,
) {
    $assertion = expect($content);

    $assertion->{$expectation}(...$arguments);
})->with([
    'XML' => [
        'toBeXml',
        '<?xml version="1.0" encoding="UTF-8"?><feed label="A &amp; B &quot;quoted&quot;"><item>Привет &lt;мир&gt; 😀</item></feed>',
        [],
    ],
    'RSS' => [
        'toBeRss',
        '<?xml version="1.0" encoding="UTF-8"?><rss version="2.0"><channel><title>A &amp; B</title><item><description>Привет &lt;мир&gt; 😀</description></item></channel></rss>',
        [],
    ],
    'JSON' => [
        'toBeJsonDocument',
        '{"value":"A & B \"quoted\" <tag> 😀","line":"first\nsecond"}',
        [],
    ],
    'JSON Lines' => [
        'toBeJsonLines',
        '{"value":"A & B \"quoted\""}' . "\r\n" . '{"value":"Привет <мир> 😀"}' . "\r\n",
        [],
    ],
    'CSV' => [
        'toBeCsv',
        '"A;B";"value ""quoted""";"Первая строка' . "\r\n" . 'Вторая строка"' . "\r\n"
            . '"😀";"<tag> & value";""',
        [[
            [
                'A;B',
                'value "quoted"',
                "Первая строка\r\nВторая строка",
            ],
            [
                '😀',
                '<tag> & value',
                '',
            ],
        ]],
    ],
]);

test('rejects malformed format fixtures', function (string $expectation, string $content) {
    expect(fn () => expect($content)->{$expectation}())
        ->toThrow(RuntimeException::class);
})->with([
    'XML' => [
        'toBeXml',
        '<feed><item></feed>',
    ],
    'RSS' => [
        'toBeRss',
        '<feed><channel/></feed>',
    ],
    'JSON' => [
        'toBeJsonDocument',
        '{"value":}',
    ],
    'JSON Lines' => [
        'toBeJsonLines',
        '{"id":1}' . "\n" . '{"id":}',
    ],
    'CSV' => [
        'toBeCsv',
        "first;second\nonly-one",
    ],
]);

test('parses semantic values without losing special characters', function () {
    $json  = parseJsonDocument('{"value":"A & B \"quoted\" <tag> 😀"}');
    $lines = parseJsonLines(
        '{"value":"A & B \"quoted\""}' . "\n" . '{"value":"Привет <мир> 😀"}'
    );
    $xml = parseXmlDocument(
        '<feed label="A &amp; B &quot;quoted&quot;"><item>Привет &lt;мир&gt; 😀</item></feed>'
    );

    expect($json)
        ->toBe(['value' => 'A & B "quoted" <tag> 😀'])
        ->and($lines)
        ->toBe([
            ['value' => 'A & B "quoted"'],
            ['value' => 'Привет <мир> 😀'],
        ])
        ->and($xml->documentElement?->getAttribute('label'))
        ->toBe('A & B "quoted"')
        ->and($xml->getElementsByTagName('item')->item(0)?->textContent)
        ->toBe('Привет <мир> 😀');
});

test('reports generated feed read failures with path context', function () {
    $directory = (new TemporaryDirectory)->create();
    $path      = $directory->path('missing.json');

    try {
        expect(fn () => readFeedFile($path))
            ->toThrow(RuntimeException::class, "Unable to read generated feed file: [$path].");
    } finally {
        $directory->delete();
    }
});

test('keeps JSON serialization exceptions with JSON Lines record context', function () {
    $failure = null;

    try {
        parseJsonLines('{"id":1}' . "\n" . "{\"value\":\"\xB1\x31\"}");
    } catch (RuntimeException $exception) {
        $failure = $exception;
    }

    expect($failure)
        ->toBeInstanceOf(RuntimeException::class)
        ->and($failure?->getMessage())
        ->toContain('Invalid JSON Lines record at line [2]:')
        ->and($failure?->getPrevious())
        ->toBeInstanceOf(JsonException::class);
});
