<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Presets\Info\YandexFeedInfo;

test('preserves the category name parameter for named arguments', function () {
    $info = (new YandexFeedInfo)->category(id: 10, name: 'Electronics');

    expect($info->toArray()['categories']['@category'])->toBe([
        [
            '@attributes' => ['id' => 10],
            '@value'      => 'Electronics',
        ],
    ]);
});

test('passes a custom category array through unchanged', function () {
    $category = [
        '@attributes' => [
            'id'       => 20,
            'parentId' => 10,
        ],
        '@value' => 'Smartphones',
        'custom' => ['enabled' => true],
    ];

    $info = (new YandexFeedInfo)->category('ignored', $category);

    expect($info->toArray()['categories']['@category'])->toBe([$category]);
});

test('accepts default and custom structures in a category collection', function () {
    $category = [
        '@attributes' => [
            'id'       => 20,
            'parentId' => 10,
        ],
        '@value' => 'Smartphones',
    ];

    $info = (new YandexFeedInfo)->categories([
        10       => 'Electronics',
        'custom' => $category,
    ]);

    expect($info->toArray()['categories']['@category'])->toBe([
        [
            '@attributes' => ['id' => 10],
            '@value'      => 'Electronics',
        ],
        $category,
    ]);
});
