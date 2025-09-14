<?php

declare(strict_types=1);

use Workbench\App\Data\ProductFakeData;
use Workbench\App\Models\News;
use Workbench\App\Models\Product;

function createNews(...$sequence): void
{
    News::factory()->count(3)->sequence(
        ...$sequence
    )->createMany();
}

function createProducts(int $count = 3): void
{
    Product::factory()->count($count)->sequence(
        ...ProductFakeData::toArray()
    )->create();
}
