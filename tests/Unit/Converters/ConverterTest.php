<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Contracts\Transformer;
use DragonCode\LaravelFeed\Converters\JsonConverter;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use DragonCode\LaravelFeed\Services\TransformerService;

final class ConstructorConfiguredTransformer implements Transformer
{
    public static int $instances = 0;

    public function __construct()
    {
        self::$instances++;
    }

    public function allow(mixed $value): bool
    {
        return is_string($value);
    }

    public function transform(mixed $value): string
    {
        return $value . ':custom';
    }
}

final class ConstructorConfiguredJsonConverter extends JsonConverter
{
    public function __construct(TransformerService $transformer)
    {
        parent::__construct(JSON_THROW_ON_ERROR, false, $transformer);

        $this->transformers[] = ConstructorConfiguredTransformer::class;
    }
}

final class InfoOverriddenJsonConverter extends JsonConverter
{
    public function info(array $info, bool $afterRoot): string
    {
        $info['overridden'] = 'yes';

        return parent::info($info, $afterRoot);
    }
}

test('compiles the transformer pipeline after subclass construction', function () {
    ConstructorConfiguredTransformer::$instances = 0;

    $first = mock(FeedItem::class);
    $first->shouldReceive('toArray')->once()->andReturn(['value' => 'first']);

    $second = mock(FeedItem::class);
    $second->shouldReceive('toArray')->once()->andReturn(['value' => 'second']);

    $converter = new ConstructorConfiguredJsonConverter(
        new TransformerService(app(), [])
    );

    expect($converter->item($first, true))
        ->toBe('{"value":"first:custom"}')
        ->and($converter->item($second, true))
        ->toBe('{"value":"second:custom"}')
        ->and(ConstructorConfiguredTransformer::$instances)
        ->toBe(1);
});

test('preserves info overrides during file-aware serialization', function () {
    $converter = new InfoOverriddenJsonConverter(
        JSON_THROW_ON_ERROR,
        false,
        new TransformerService(app(), [])
    );

    expect($converter->infoForFile(['name' => 'Laravel'], true, false))
        ->toBe('{"name":"Laravel","overridden":"yes"}')
        ->and($converter->info(['name' => 'Laravel'], true))
        ->toBe('{"name":"Laravel","overridden":"yes"},')
        ->and($converter->infoForFile(['name' => 'Laravel'], true, true))
        ->toBe('{"name":"Laravel","overridden":"yes"},');
});
