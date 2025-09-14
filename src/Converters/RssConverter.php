<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Converters;

use DOMNode;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;

class RssConverter extends XmlConverter
{
    protected function performBox(FeedItem $item): DOMNode
    {
        $element = $this->createElement('item');

        if ($values = $item->attributes()) {
            $this->setAttributes($element, $values);
        }

        return $element;
    }
}
