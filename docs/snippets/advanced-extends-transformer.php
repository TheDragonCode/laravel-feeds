<?php

declare(strict_types=1);

namespace App\Feeds\Transformers;

use App\Services\PriceFormatter;
use DragonCode\LaravelFeed\Contracts\Transformer;

class PriceTransformer implements Transformer
{
    public function __construct(
        protected PriceFormatter $formatter,
    ) {}

    public function allow(mixed $value): bool
    {
        return is_float($value);
    }

    public function transform(mixed $value): string
    {
        return $this->formatter->format($value);
    }
}
