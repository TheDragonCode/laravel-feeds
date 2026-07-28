<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Services;

use Closure;
use DragonCode\LaravelFeed\Contracts\Transformer;
use Illuminate\Container\Attributes\Config;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;

class TransformerService
{
    public function __construct(
        protected Container $container,
        #[Config('feeds.transformers', [])]
        protected array $configuredTransformers,
    ) {}

    public function pipeline(array $transformers = [], array $prependTransformers = []): Closure
    {
        $pipeline = $this->transformers($transformers, $prependTransformers);

        return static function (mixed $value) use ($pipeline): bool|float|int|string|null {
            foreach ($pipeline as $transformer) {
                if ($transformer->allow($value)) {
                    $value = $transformer->transform($value);
                }
            }

            return $value;
        };
    }

    protected function transformers(array $transformers, array $prependTransformers): array
    {
        return (new Collection($prependTransformers))
            ->merge($this->configuredTransformers)
            ->merge($transformers)
            ->map(fn (string $transformer): Transformer => $this->container->make($transformer))
            ->unique()
            ->all();
    }
}
