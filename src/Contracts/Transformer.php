<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Contracts;

interface Transformer
{
    public function allow(mixed $value): bool;

    public function transform(mixed $value): string;
}
