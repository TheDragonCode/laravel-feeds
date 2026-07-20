<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Services;

use Closure;
use DragonCode\LaravelFeed\Contracts\Transformer;
use Illuminate\Container\Attributes\Config;
use Illuminate\Container\Container as LaravelContainer;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;

use function config;
use function implode;

class TransformerService
{
    protected array $pipelines = [];

    protected Container $container;

    protected array $configuredTransformers;

    public function __construct(
        ?Container $container = null,
        #[Config('feeds.transformers')]
        ?array $configuredTransformers = null,
    ) {
        $this->container              = $container              ?? LaravelContainer::getInstance();
        $this->configuredTransformers = $configuredTransformers ?? config('feeds.transformers', []);
    }

    public function transform(mixed $value, array $transformers = []): bool|float|int|string|null
    {
        $key = implode('|', $transformers);

        return ($this->pipelines[$key] ??= $this->pipeline($transformers))($value);
    }

    public function pipeline(array $transformers = []): Closure
    {
        $pipeline = $this->transformers($transformers);

        return static function (mixed $value) use ($pipeline): bool|float|int|string|null {
            foreach ($pipeline as $transformer) {
                if ($transformer->allow($value)) {
                    $value = $transformer->transform($value);
                }
            }

            return $value;
        };
    }

    protected function transformers(array $transformers): array
    {
        return (new Collection($this->configuredTransformers))
            ->merge($transformers)
            ->map(fn (string $transformer): Transformer => $this->container->make($transformer))
            ->unique()
            ->all();
    }
}
