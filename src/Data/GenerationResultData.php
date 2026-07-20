<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Data;

readonly class GenerationResultData
{
    public function __construct(
        public array $paths,
        public array $records,
    ) {}
}
