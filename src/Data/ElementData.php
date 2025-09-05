<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Data;

readonly class ElementData
{
    public function __construct(
        public ?string $name = null,
        public array $attributes = [],
        public bool $beforeInfo = true
    ) {}
}
