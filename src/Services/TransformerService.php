<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Services;

use Illuminate\Support\Collection;

use function config;

class TransformerService
{
    public function transform(mixed $value, array $transformers = []): bool|float|int|string|null
    {
        foreach ($this->transformers($transformers) as $transformer) {
            if ($transformer->allow($value)) {
                $value = $transformer->transform($value);
            }
        }

        return $value;
    }

    /**
     * @return \DragonCode\LaravelFeed\Contracts\Transformer[]
     */
    protected function transformers(array $transformers): array
    {
        return (new Collection(config('feeds.transformers')))
            ->merge($transformers)
            ->map(static fn (string $transformer) => new $transformer)
            ->unique()
            ->all();
    }
}
