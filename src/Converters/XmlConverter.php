<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Converters;

use DOMDocument;
use DOMNode;
use DragonCode\LaravelFeed\Data\ElementData;
use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use DragonCode\LaravelFeed\Services\TransformerService;
use DragonCode\LaravelFeed\Transformers\SpecialCharsTransformer;
use Illuminate\Container\Attributes\Config;
use Illuminate\Support\Str;

use function collect;
use function is_array;
use function sprintf;
use function str_replace;
use function str_starts_with;
use function trim;

class XmlConverter extends Converter
{
    protected DOMDocument $document;

    protected array $transformers = [
        SpecialCharsTransformer::class,
    ];

    public function __construct(
        #[Config('feeds.pretty')]
        bool $pretty,
        TransformerService $transformer,
    ) {
        parent::__construct($pretty, $transformer);

        $this->document = new DOMDocument('1.0', 'UTF-8');

        $this->document->formatOutput       = $pretty;
        $this->document->preserveWhiteSpace = ! $pretty;
    }

    public function header(Feed $feed): string
    {
        if (empty($value = $feed->header())) {
            return '';
        }

        return trim($value) . PHP_EOL;
    }

    public function footer(Feed $feed): string
    {
        $value = '';

        if ($name = $feed->root()->name) {
            $value .= "\n\n</$name>\n";
        }

        return $value . $feed->footer();
    }

    public function root(Feed $feed): string
    {
        return ! empty($feed->root()->attributes)
            ? sprintf("<%s %s>\n\n", $feed->root()->name, $this->rootAttributes($feed->root()))
            : sprintf("<%s>\n\n", $feed->root()->name);
    }

    public function item(FeedItem $item, bool $isLast): string
    {
        $box = $this->performBox($item);

        $this->performItem($box, $item->toArray());

        return $this->toXml($box);
    }

    public function info(array $info, bool $afterRoot): string
    {
        $box = $this->document->createDocumentFragment();

        $this->performItem($box, $info);

        return $this->toXml($box);
    }

    protected function performBox(FeedItem $item): DOMNode
    {
        $element = $this->createElement($item->name());

        if ($values = $item->attributes()) {
            $this->setAttributes($element, $values);
        }

        return $element;
    }

    protected function performItem(DOMNode $parent, array $items): void
    {
        foreach ($items as $key => $value) {
            $key = $this->convertKey($key);

            match (true) {
                $this->isAttributes($key) => $this->setAttributes($parent, $value),
                $this->isCData($key)      => $this->setCData($parent, $value),
                $this->isMixed($key)      => $this->setMixed($parent, $value),
                $this->isValue($key)      => $this->setRaw($parent, $value),
                $this->isPrefixed($key)   => $this->setItemsArray($parent, $value, $key),
                default                   => $this->setItems($parent, $key, $value),
            };
        }
    }

    protected function isAttributes(string $key): bool
    {
        return $key === '@attributes';
    }

    protected function isCData(string $key): bool
    {
        return $key === '@cdata';
    }

    protected function isMixed(string $key): bool
    {
        return $key === '@mixed';
    }

    protected function isValue(string $key): bool
    {
        return $key === '@value';
    }

    protected function isPrefixed(string $key): bool
    {
        return str_starts_with($key, '@');
    }

    protected function createElement(string $name, mixed $value = ''): DOMNode
    {
        return $this->document->createElement($name, (string) $this->transformValue($value));
    }

    protected function setAttributes(DOMNode $element, array $attributes): void
    {
        foreach ($attributes as $key => $value) {
            $element->setAttribute($key, (string) $this->transformValue($value));
        }
    }

    protected function setCData(DOMNode $element, string $value): void
    {
        $element->appendChild(
            $this->document->createCDATASection($value)
        );
    }

    protected function setMixed(DOMNode $element, string $value): void
    {
        $fragment = $this->document->createDocumentFragment();
        $fragment->appendXML($value);

        $element->appendChild($fragment);
    }

    protected function setItemsArray(DOMNode $parent, array $value, string $key): void
    {
        $key = Str::substr($key, 1);

        foreach ($value as $item) {
            $this->setItems($parent, $key, $item);
        }
    }

    protected function setItems(DOMNode $parent, string $key, mixed $value): void
    {
        $element = $this->createElement($key, is_array($value) ? '' : $value);

        if (is_array($value)) {
            $this->performItem($element, $value);
        }

        $parent->appendChild($element);
    }

    protected function setRaw(DOMNode $parent, mixed $value): void
    {
        $parent->nodeValue = (string) $this->transformValue($value);
    }

    protected function rootAttributes(ElementData $item): string
    {
        return collect($item->attributes)
            ->map(fn (mixed $value, int|string $key) => sprintf('%s="%s"', $key, $value))
            ->implode(' ');
    }

    protected function toXml(DOMNode $item): string
    {
        return $this->document->saveXML($item, LIBXML_COMPACT);
    }

    protected function convertKey(int|string $key): string
    {
        return str_replace(' ', '_', (string) $key);
    }
}
