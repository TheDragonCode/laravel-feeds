<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Converters;

use DOMDocument;
use DOMNode;
use DragonCode\LaravelFeed\Exceptions\InvalidXmlCDataException;
use DragonCode\LaravelFeed\Exceptions\InvalidXmlFragmentException;
use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use DragonCode\LaravelFeed\Services\TransformerService;
use DragonCode\LaravelFeed\Transformers\SpecialCharsTransformer;
use Illuminate\Container\Attributes\Config;
use Illuminate\Support\Str;
use Throwable;

use function array_slice;
use function count;
use function is_array;
use function libxml_clear_errors;
use function libxml_get_errors;
use function libxml_use_internal_errors;
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
        $root = $feed->root();

        if (! $root->name) {
            return '';
        }

        $element = $this->createElement($root->name);

        if ($root->attributes) {
            $this->setAttributes($element, $root->attributes);
        }

        return Str::replaceEnd('/>', ">\n\n", $this->encode($element));
    }

    public function item(FeedItem $item, bool $isLast): string
    {
        $box = $this->performBox($item);

        $this->performItem($box, $item->toArray());

        return $this->encode($box);
    }

    public function info(array $info, bool $afterRoot): string
    {
        $box = $this->document->createDocumentFragment();

        $this->performItem($box, $info);

        return $this->encode($box);
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
        $element = $this->document->createElement($name);

        $this->appendText($element, $value);

        return $element;
    }

    protected function setAttributes(DOMNode $element, array $attributes): void
    {
        foreach ($attributes as $key => $value) {
            $element->setAttribute($key, (string) $this->transformValue($value));
        }
    }

    protected function setCData(DOMNode $element, string $value): void
    {
        try {
            $section = $this->document->createCDATASection($value);
        } catch (Throwable $exception) {
            throw new InvalidXmlCDataException($exception);
        }

        if ($section === false) {
            throw new InvalidXmlCDataException;
        }

        $element->appendChild($section);
    }

    protected function setMixed(DOMNode $element, string $value): void
    {
        $fragment       = $this->document->createDocumentFragment();
        $internalErrors = libxml_use_internal_errors(true);
        $errorCount     = count(libxml_get_errors());
        $reason         = null;

        try {
            $appended = $fragment->appendXML($value);
            $errors   = array_slice(libxml_get_errors(), $errorCount);
            $reason   = isset($errors[0]) ? trim($errors[0]->message) : null;
        } catch (Throwable $exception) {
            throw new InvalidXmlFragmentException(previous: $exception);
        } finally {
            if (! $internalErrors) {
                libxml_clear_errors();
            }

            libxml_use_internal_errors($internalErrors);
        }

        if (! $appended) {
            throw new InvalidXmlFragmentException($reason);
        }

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
        while ($parent->firstChild) {
            $parent->removeChild($parent->firstChild);
        }

        $this->appendText($parent, $value);
    }

    protected function appendText(DOMNode $parent, mixed $value): void
    {
        $value = (string) $this->transformValue($value);

        if ($value === '') {
            return;
        }

        $parent->appendChild(
            $this->document->createTextNode($value)
        );
    }

    protected function encode(DOMNode $item): string
    {
        return $this->document->saveXML($item, LIBXML_COMPACT);
    }

    protected function convertKey(int|string $key): string
    {
        return str_replace(' ', '_', (string) $key);
    }
}
