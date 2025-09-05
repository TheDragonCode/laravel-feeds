<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Enums;

enum FeedFormatEnum: string
{
    case Xml  = 'xml';
    case Json = 'json';
}
