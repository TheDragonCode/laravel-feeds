<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Presets\Info\YandexFeedInfo;

test('replaces existing currencies through currency', function () {
    $info = (new YandexFeedInfo)
        ->currency('USD', 1.0)
        ->currency('EUR', 0.9, replace: true);

    expect($info->toArray()['currencies']['@currency'])->toBe([
        [
            '@attributes' => [
                'id'   => 'EUR',
                'rate' => 0.9,
            ],
        ],
    ]);
});

test('preserves the category name parameter for named arguments', function () {
    $info = (new YandexFeedInfo)->category(id: 10, name: 'Electronics');

    expect($info->toArray()['categories']['@category'])->toBe([
        [
            '@attributes' => ['id' => 10],
            '@value'      => 'Electronics',
        ],
    ]);
});

test('replaces existing categories through category', function () {
    $info = (new YandexFeedInfo)
        ->category(10, 'Old category')
        ->category(20, 'New category', replace: true);

    expect($info->toArray()['categories']['@category'])->toBe([
        [
            '@attributes' => ['id' => 20],
            '@value'      => 'New category',
        ],
    ]);
});

test('keeps category compatible with string-only overrides', function () {
    $parameter = (new ReflectionMethod(YandexFeedInfo::class, 'category'))
        ->getParameters()[1];

    expect((string) $parameter->getType())->toBe('string');
});

test('passes a raw category array through unchanged', function () {
    $category = [
        '@attributes' => [
            'id'       => 20,
            'parentId' => 10,
        ],
        '@value' => 'Smartphones',
        'custom' => ['enabled' => true],
    ];

    $info = (new YandexFeedInfo)
        ->category(10, 'Old category')
        ->rawCategory($category, replace: true);

    expect($info->toArray()['categories']['@category'])->toBe([$category]);
});

test('dispatches mixed category collections without widening legacy overrides', function () {
    $category = [
        '@attributes' => [
            'id'       => 20,
            'parentId' => 10,
        ],
        '@value' => 'Smartphones',
    ];

    $info = new class extends YandexFeedInfo {
        public array $categoryCalls = [];

        public function category(int|string $id, string $name, bool $replace = false): static
        {
            $this->categoryCalls[] = [$id, $name, $replace];

            return parent::category($id, $name, $replace);
        }
    };

    $info->categories([
        10       => 'Electronics',
        'custom' => $category,
    ]);

    expect($info->categoryCalls)
        ->toBe([[10, 'Electronics', false]])
        ->and($info->toArray()['categories']['@category'])
        ->toBe([
            [
                '@attributes' => ['id' => 10],
                '@value'      => 'Electronics',
            ],
            $category,
        ]);
});
