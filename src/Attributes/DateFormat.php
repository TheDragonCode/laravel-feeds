<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class DateFormat
{
    public function __construct(
        public readonly string $format,
    ) {}
}
