<?php

declare(strict_types=1);

namespace App\Feeds\Items;

use DragonCode\LaravelFeed\Feeds\Items\FeedItem;

use function route;

/** @property-read \App\Models\Product $model */
class YandexFeedItem extends FeedItem
{
    public function name(): string
    {
        return 'offer';
    }

    public function attributes(): array
    {
        return [
            'id' => $this->model->id,

            'available' => ! empty($this->model->quantity) ? 'true' : 'false',

            'type' => 'vendor.model',
        ];
    }

    public function toArray(): array
    {
        return [
            'url' => route('products.show', $this->model->slug),

            'barcode'     => $this->model->article,
            'name'        => $this->model->title,
            'description' => $this->model->description,

            'delivery' => 'true',
            'price'    => $this->model->price,

            'currencyId' => 'RUR',
            'vendor'     => $this->model->brand,

            '@picture' => $this->model->images,

            '@param' => [
                [
                    '@attributes' => ['name' => 'Артикул'],
                    '@value'      => $this->model->article,
                ],
                [
                    '@attributes' => ['name' => 'Код цвета'],
                    '@value'      => fake()->randomDigit(),
                ],
                [
                    '@attributes' => ['name' => 'Пол'],
                    '@value'      => fake()->boolean() ? 'male' : 'female',
                ],
            ],
        ];
    }
}
