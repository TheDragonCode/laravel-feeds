<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class Config
{
    public function __construct(
        public string $name,
        public mixed $value,
    ) {}
}
