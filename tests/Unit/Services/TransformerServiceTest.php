<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Contracts\Transformer;
use DragonCode\LaravelFeed\Services\TransformerService;

final class TransformerPipelineDependency {}

final class TransformerPipelineLocal implements Transformer
{
    public static int $instances = 0;

    public static int $calls = 0;

    public function __construct(
        public TransformerPipelineDependency $dependency,
    ) {
        self::$instances++;
    }

    public function allow(mixed $value): bool
    {
        return is_string($value);
    }

    public function transform(mixed $value): string
    {
        self::$calls++;

        return $value . ':local';
    }
}

final class TransformerPipelineFirst implements Transformer
{
    public static int $instances = 0;

    public static int $calls = 0;

    public function __construct(
        public TransformerPipelineDependency $dependency,
    ) {
        self::$instances++;
    }

    public function allow(mixed $value): bool
    {
        return is_string($value);
    }

    public function transform(mixed $value): string
    {
        self::$calls++;

        return $value . ':first';
    }
}

final class TransformerPipelineSecond implements Transformer
{
    public static int $instances = 0;

    public static int $calls = 0;

    public function __construct(
        public TransformerPipelineDependency $dependency,
    ) {
        self::$instances++;
    }

    public function allow(mixed $value): bool
    {
        return is_string($value);
    }

    public function transform(mixed $value): string
    {
        self::$calls++;

        return $value . ':second';
    }
}

beforeEach(function () {
    TransformerPipelineLocal::$instances  = 0;
    TransformerPipelineLocal::$calls      = 0;
    TransformerPipelineFirst::$instances  = 0;
    TransformerPipelineFirst::$calls      = 0;
    TransformerPipelineSecond::$instances = 0;
    TransformerPipelineSecond::$calls     = 0;
});

test('resolves an ordered transformer pipeline once through the container', function () {
    app()->singleton(TransformerPipelineDependency::class);

    $service  = new TransformerService(app(), [TransformerPipelineFirst::class]);
    $pipeline = $service->pipeline([TransformerPipelineSecond::class]);

    expect($pipeline('value'))
        ->toBe('value:first:second')
        ->and($pipeline('next'))
        ->toBe('next:first:second')
        ->and(TransformerPipelineFirst::$instances)
        ->toBe(1)
        ->and(TransformerPipelineSecond::$instances)
        ->toBe(1)
        ->and(TransformerPipelineFirst::$calls)
        ->toBe(2)
        ->and(TransformerPipelineSecond::$calls)
        ->toBe(2);
});

test('runs local transformers before configured and converter transformers', function () {
    app()->singleton(TransformerPipelineDependency::class);

    $service  = new TransformerService(app(), [TransformerPipelineFirst::class]);
    $pipeline = $service->pipeline(
        [TransformerPipelineSecond::class],
        [TransformerPipelineLocal::class]
    );

    expect($pipeline('value'))
        ->toBe('value:local:first:second')
        ->and(TransformerPipelineLocal::$instances)
        ->toBe(1)
        ->and(TransformerPipelineFirst::$instances)
        ->toBe(1)
        ->and(TransformerPipelineSecond::$instances)
        ->toBe(1)
        ->and(TransformerPipelineLocal::$calls)
        ->toBe(1)
        ->and(TransformerPipelineFirst::$calls)
        ->toBe(1)
        ->and(TransformerPipelineSecond::$calls)
        ->toBe(1);
});
