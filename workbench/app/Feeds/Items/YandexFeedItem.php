<?php

declare(strict_types=1);

namespace Workbench\App\Feeds\Items;

use DragonCode\LaravelFeed\Feeds\Items\FeedItem;

/** @property-read \Workbench\App\Models\Product $model */
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

            'available' => ! empty($this->model->quantity),

            'type' => 'vendor.model',
        ];
    }

    public function toArray(): array
    {
        return [
            'url' => route('products.show', $this->model->article),

            'barcode'     => $this->model->article,
            'name'        => $this->model->title,
            'description' => $this->model->description,

            'delivery' => 'true',
            'price'    => $this->model->price,

            'currencyId' => 'RUR',
            'vendor'     => $this->model->brand,

            '@picture' => $this->model->images,
        ];
    }
}
