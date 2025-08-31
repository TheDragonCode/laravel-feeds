<?php

declare(strict_types=1);

namespace App\Feeds\Items;

use DragonCode\LaravelFeed\Feeds\Items\FeedItem;

use function route;

/** @property-read \App\Models\Product $model */
class InstagramFeedItem extends FeedItem
{
    public function name(): string
    {
        return 'item';
    }

    public function toArray(): array
    {
        return [
            'g:id'    => $this->model->id,
            'g:title' => $this->model->title,

            'g:description' => [
                '@cdata' => $this->model->description,
            ],

            'g:brand' => $this->model->brand,

            'g:link'       => route('products.show', $this->model->slug),
            'g:image_link' => $this->model->image,

            'g:availability' => $this->model->quantity ? 'in stock' : 'out of stock',
            'g:status'       => $this->model->quantity ? 'active' : 'inactive',
            'g:price'        => $this->model->price,
        ];
    }
}
