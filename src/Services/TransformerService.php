<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Services;

use DragonCode\LaravelFeed\Transformers\SpecialCharsTransformer;
use Illuminate\Support\Collection;

use function config;

class TransformerService
{
    protected array $force = [
        SpecialCharsTransformer::class,
    ];

    public function transform(mixed $value): string
    {
        foreach ($this->transformers() as $transformer) {
            if ($transformer->allow($value)) {
                $value = $transformer->transform($value);
            }
        }

        return (string) $value;
    }

    /**
     * @return \DragonCode\LaravelFeed\Contracts\Transformer[]
     */
    protected function transformers(): array
    {
        return (new Collection(config('feeds.transformers')))
            ->merge($this->force)
            ->map(static fn (string $transformer) => new $transformer)
            ->unique()
            ->all();
    }
}
