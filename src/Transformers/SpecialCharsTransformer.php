<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Transformers;

use DragonCode\LaravelFeed\Contracts\Transformer;

class SpecialCharsTransformer implements Transformer
{
    public function allow(mixed $value): bool
    {
        return true;
    }

    public function transform(mixed $value): string
    {
        return $this->removeControlCharacters(
            htmlspecialchars((string) $value)
        );
    }

    protected function removeControlCharacters(string $value): string
    {
        return preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
    }
}
