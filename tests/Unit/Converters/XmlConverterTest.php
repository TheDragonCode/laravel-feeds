<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Converters\XmlConverter;
use DragonCode\LaravelFeed\Data\ElementData;
use DragonCode\LaravelFeed\Exceptions\InvalidXmlCDataException;
use DragonCode\LaravelFeed\Exceptions\InvalidXmlFragmentException;
use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;

test('round trips text and item attributes through DOM', function () {
    $value = 'A & B "double" \'single\' <tag> > tail';
    $cdata = $value . ' ]]> cdata';

    $item = mock(FeedItem::class);
    $item->shouldReceive('name')->once()->andReturn('item');
    $item->shouldReceive('attributes')->once()->andReturn(['symbols' => $value]);
    $item->shouldReceive('toArray')->once()->andReturn([
        'text'  => $value,
        'value' => ['discarded' => 'content', '@value' => $value],
        'cdata' => ['@cdata' => $cdata],
        'mixed' => ['@mixed' => '<strong>A &amp; B</strong>'],
    ]);

    $content  = app(XmlConverter::class)->item($item, true);
    $document = parseXmlDocument($content);
    $root     = $document->documentElement;

    expect($root?->getAttribute('symbols'))
        ->toBe($value)
        ->and($root?->getElementsByTagName('text')->item(0)?->textContent)
        ->toBe($value)
        ->and($root?->getElementsByTagName('value')->item(0)?->textContent)
        ->toBe($value)
        ->and($root?->getElementsByTagName('cdata')->item(0)?->textContent)
        ->toBe($cdata)
        ->and($root?->getElementsByTagName('mixed')->item(0)?->firstElementChild?->nodeName)
        ->toBe('strong')
        ->and($content)
        ->not->toContain('&amp;amp;');
});

test('round trips root attributes through DOM', function () {
    $value = 'A & B "double" \'single\' <tag> > tail';

    $feed = mock(Feed::class);
    $feed->shouldReceive('root')->once()->andReturn(
        new ElementData('feed', ['symbols' => $value])
    );

    $content  = app(XmlConverter::class)->root($feed) . '</feed>';
    $document = parseXmlDocument($content);

    expect($document->documentElement?->getAttribute('symbols'))
        ->toBe($value)
        ->and($content)
        ->toContain('&amp;')
        ->toContain('&quot;')
        ->toContain('&lt;')
        ->not->toContain('&amp;amp;');
});

test('omits an empty root element', function () {
    $feed = mock(Feed::class);
    $feed->shouldReceive('root')->once()->andReturn(new ElementData);

    expect(app(XmlConverter::class)->root($feed))->toBe('');
});

test('rejects invalid mixed XML fragments', function () {
    $item = mock(FeedItem::class);
    $item->shouldReceive('name')->once()->andReturn('item');
    $item->shouldReceive('attributes')->once()->andReturn([]);
    $item->shouldReceive('toArray')->once()->andReturn([
        'content' => ['@mixed' => '<broken>'],
    ]);

    expect(fn () => app(XmlConverter::class)->item($item, true))
        ->toThrow(
            InvalidXmlFragmentException::class,
            'Invalid XML fragment supplied to the [@mixed] directive.'
        );
});

test('rejects CDATA creation failures', function () {
    $converter = app(XmlConverter::class);
    $document  = new class ('1.0', 'UTF-8') extends DOMDocument {
        public function createCDATASection(string $data): DOMCdataSection|false
        {
            return false;
        }
    };

    (new ReflectionProperty(XmlConverter::class, 'document'))->setValue($converter, $document);

    $item = mock(FeedItem::class);
    $item->shouldReceive('name')->once()->andReturn('item');
    $item->shouldReceive('attributes')->once()->andReturn([]);
    $item->shouldReceive('toArray')->once()->andReturn([
        'content' => ['@cdata' => 'value'],
    ]);

    expect(fn () => $converter->item($item, true))
        ->toThrow(
            InvalidXmlCDataException::class,
            'Unable to create an XML CDATA section for the [@cdata] directive.'
        );
});

test('wraps CDATA creation exceptions', function () {
    $converter = app(XmlConverter::class);
    $document  = new class ('1.0', 'UTF-8') extends DOMDocument {
        public function createCDATASection(string $data): DOMCdataSection|false
        {
            throw new DOMException('CDATA failure.');
        }
    };

    (new ReflectionProperty(XmlConverter::class, 'document'))->setValue($converter, $document);

    $item = mock(FeedItem::class);
    $item->shouldReceive('name')->once()->andReturn('item');
    $item->shouldReceive('attributes')->once()->andReturn([]);
    $item->shouldReceive('toArray')->once()->andReturn([
        'content' => ['@cdata' => 'value'],
    ]);

    $thrown = null;

    try {
        $converter->item($item, true);
    } catch (InvalidXmlCDataException $exception) {
        $thrown = $exception;
    }

    expect($thrown)
        ->toBeInstanceOf(InvalidXmlCDataException::class)
        ->and($thrown?->getPrevious())
        ->toBeInstanceOf(DOMException::class)
        ->and($thrown?->getPrevious()?->getMessage())
        ->toBe('CDATA failure.');
});

test('wraps mixed fragment creation exceptions', function () {
    $converter = app(XmlConverter::class);
    $document  = new class ('1.0', 'UTF-8') extends DOMDocument {
        public function createDocumentFragment(): DOMDocumentFragment
        {
            return new class extends DOMDocumentFragment {
                public function appendXML(string $data): bool
                {
                    throw new DOMException('Fragment failure.');
                }
            };
        }
    };

    (new ReflectionProperty(XmlConverter::class, 'document'))->setValue($converter, $document);

    $item = mock(FeedItem::class);
    $item->shouldReceive('name')->once()->andReturn('item');
    $item->shouldReceive('attributes')->once()->andReturn([]);
    $item->shouldReceive('toArray')->once()->andReturn([
        'content' => ['@mixed' => '<value/>'],
    ]);

    $thrown = null;

    try {
        $converter->item($item, true);
    } catch (InvalidXmlFragmentException $exception) {
        $thrown = $exception;
    }

    expect($thrown)
        ->toBeInstanceOf(InvalidXmlFragmentException::class)
        ->and($thrown?->getPrevious())
        ->toBeInstanceOf(DOMException::class)
        ->and($thrown?->getPrevious()?->getMessage())
        ->toBe('Fragment failure.');
});
