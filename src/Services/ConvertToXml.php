<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Services;

use DOMDocument;
use DOMElement;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use Illuminate\Container\Attributes\Config;
use Illuminate\Support\Str;

use function htmlspecialchars;
use function is_array;
use function is_bool;
use function preg_replace;
use function str_replace;
use function str_starts_with;

class ConvertToXml
{
    public function __construct(
        #[Config('feeds.pretty')]
        bool $pretty,
        protected DOMDocument $document
    ) {
        $this->document->formatOutput       = $pretty;
        $this->document->preserveWhiteSpace = ! $pretty;
    }

    public function convert(FeedItem $item): string
    {
        $box = $this->performBox($item);

        $this->performItem($box, $item->toArray());

        return $this->toXml($box);
    }

    protected function performBox(FeedItem $item): DOMElement
    {
        $element = $this->createElement($item->name());

        if ($values = $item->attributes()) {
            $this->setAttributes($element, $values);
        }

        return $element;
    }

    protected function performItem(DOMElement $parent, array $items): void
    {
        foreach ($items as $key => $value) {
            $key = $this->convertKey($key);

            match (true) {
                $this->isAttributes($key) => $this->setAttributes($parent, $value),
                $this->isCData($key)      => $this->setCData($parent, $value),
                $this->isMixed($key)      => $this->setMixed($parent, $value),
                $this->isArray($key)      => $this->setItemsArray($parent, $value, $key),
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

    protected function isArray(string $key): bool
    {
        return str_starts_with($key, '@');
    }

    protected function createElement(string $name, bool|float|int|string|null $value = ''): DOMElement
    {
        return $this->document->createElement($name, $this->convertValue($value));
    }

    protected function setAttributes(DOMElement $element, array $attributes): void
    {
        foreach ($attributes as $key => $value) {
            $element->setAttribute($key, $this->convertValue($value));
        }
    }

    protected function setCData(DOMElement $element, string $value): void
    {
        $element->appendChild(
            $this->document->createCDATASection($value)
        );
    }

    protected function setMixed(DOMElement $element, string $value): void
    {
        $fragment = $this->document->createDocumentFragment();
        $fragment->appendXML($value);

        $element->appendChild($fragment);
    }

    protected function setItemsArray(DOMElement $parent, mixed $value, string $key): void
    {
        $key = Str::substr($key, 1);

        foreach ($value as $item) {
            $this->setItems($parent, $key, $item);
        }
    }

    protected function setItems(DOMElement $parent, string $key, mixed $value): void
    {
        $element = $this->createElement($key, is_array($value) ? '' : $this->convertValue($value));

        if (is_array($value)) {
            $this->performItem($element, $value);
        }

        $parent->appendChild($element);
    }

    protected function toXml(DOMElement $item): string
    {
        return $this->document->saveXML($item);
    }

    protected function convertKey(int|string $key): string
    {
        return str_replace(' ', '_', (string) $key);
    }

    protected function convertValue(bool|float|int|string|null $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return $this->removeControlCharacters(
            htmlspecialchars((string) $value)
        );
    }

    protected function removeControlCharacters(string $value): string
    {
        return preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
    }
}
