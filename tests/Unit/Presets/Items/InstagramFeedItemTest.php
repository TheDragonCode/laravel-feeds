<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Presets\Items\InstagramFeedItem;
use Illuminate\Database\Eloquent\Model;

function instagramFeedItem(): InstagramFeedItem
{
    $model = new class extends Model {};

    $model->setAttribute($model->getKeyName(), 123);

    return new InstagramFeedItem($model);
}

test('aligns required setter arguments with non-nullable properties', function (string $method, string $property) {
    $parameterType = (new ReflectionMethod(InstagramFeedItem::class, $method))
        ->getParameters()[0]
        ->getType();
    $propertyType = (new ReflectionProperty(InstagramFeedItem::class, $property))->getType();

    expect($parameterType)
        ->not->toBeNull()
        ->and($parameterType?->allowsNull())
        ->toBeFalse()
        ->and($propertyType)
        ->not->toBeNull()
        ->and($propertyType?->allowsNull())
        ->toBeFalse();
})->with([
    'image'        => ['image', 'image'],
    'condition'    => ['condition', 'condition'],
    'availability' => ['availability', 'availability'],
    'price'        => ['price', 'price'],
]);

test('aligns the nullable status argument with its property', function () {
    $parameterType = (new ReflectionMethod(InstagramFeedItem::class, 'status'))
        ->getParameters()[0]
        ->getType();
    $propertyType = (new ReflectionProperty(InstagramFeedItem::class, 'status'))->getType();

    expect($parameterType)
        ->not->toBeNull()
        ->and($parameterType?->allowsNull())
        ->toBeTrue()
        ->and($propertyType)
        ->not->toBeNull()
        ->and($propertyType?->allowsNull())
        ->toBeTrue();
});

test('omits accepted nullable values from serialization', function () {
    $item = instagramFeedItem()
        ->title('Product')
        ->description('Description')
        ->url('https://example.test/products/123')
        ->image('https://example.test/images/123.jpg')
        ->images(null)
        ->price(10.0, null)
        ->group(null)
        ->status(null)
        ->googleCategory(null)
        ->facebookCategory(null);

    expect($item->toArray())->toBe([
        'g:id'           => 123,
        'g:title'        => ['@cdata' => 'Product'],
        'g:description'  => ['@cdata' => 'Description'],
        'g:link'         => 'https://example.test/products/123',
        'g:image_link'   => 'https://example.test/images/123.jpg',
        'g:condition'    => 'new',
        'g:availability' => 'in stock',
        'g:price'        => 10.0,
        'g:sale_price'   => 10.0,
    ]);
});

test('fails serialization when a required field is absent', function (string $missing) {
    $item = instagramFeedItem();

    if ($missing !== 'title') {
        $item->title('Product');
    }

    if ($missing !== 'description') {
        $item->description('Description');
    }

    if ($missing !== 'url') {
        $item->url('https://example.test/products/123');
    }

    if ($missing !== 'image') {
        $item->image('https://example.test/images/123.jpg');
    }

    if ($missing !== 'price') {
        $item->price(10.0);
    }

    expect(fn () => $item->toArray())->toThrow(Error::class);
})->with([
    'title',
    'description',
    'url',
    'image',
    'price',
]);
