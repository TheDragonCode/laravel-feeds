<?php

declare(strict_types=1);

namespace App\Feeds;

use App\Feeds\Transformers\PriceTransformer;
use App\Models\Product;
use DragonCode\LaravelFeed\Feeds\Feed;
use Illuminate\Database\Eloquent\Builder;

class ProductFeed extends Feed
{
    public function builder(): Builder
    {
        return Product::query();
    }

    public function transformers(): array
    {
        return [
            PriceTransformer::class,
        ];
    }
}
