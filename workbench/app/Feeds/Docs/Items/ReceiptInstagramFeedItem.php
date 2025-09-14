<?php

declare(strict_types=1);

namespace Workbench\App\Feeds\Docs\Items;

use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use Illuminate\Support\Arr;

use function collect;
use function fake;

/** @property-read \Workbench\App\Models\Product $model */
class ReceiptInstagramFeedItem extends FeedItem
{
    public function name(): string
    {
        return 'item';
    }

    public function toArray(): array
    {
        return [
            'g:id' => $this->model->id,

            'g:title'       => ['@cdata' => $this->model->title],
            'g:description' => ['@cdata' => $this->model->description],

            'g:link'       => $this->model->url,
            'g:image_link' => $this->firstImage(),

            '@g:additional_image_link' => $this->images(),

            'g:brand'        => $this->model->brand,
            'g:condition'    => 'new',
            'g:availability' => 'in stock',

            'g:price'      => $this->model->price,
            'g:sale_price' => $this->model->price,

            'g:item_group_id' => 12345,

            'g:status' => 'active',

            'g:color' => ['@cdata' => fake()->colorName()],

            'g:size' => fake()->numberBetween(10, 50),

            'g:age_group' => 'adult',

            'g:material' => ['@cdata' => fake()->word()],
            'g:pattern'  => ['@cdata' => 'regular'],

            'g:google_product_category' => 1000,
            'g:fb_product_category'     => 2000,
        ];
    }

    protected function firstImage(): string
    {
        return Arr::first($this->model->images);
    }

    protected function images(): array
    {
        return collect($this->model->images)->skip(1)->all();
    }
}
