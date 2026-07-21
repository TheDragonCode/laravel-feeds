<?php

declare(strict_types=1);

use Tests\Helpers\Benchmark\RegressionFeedModel;

function makeRegressionFeedModels(int $count): array
{
    $createdAt = new DateTimeImmutable('2026-01-01T00:00:00+00:00');
    $models    = [];

    for ($id = 1; $id <= $count; $id++) {
        $model = new RegressionFeedModel;
        $model->setRawAttributes([
            'id'          => $id,
            'sku'         => 'SKU-' . $id,
            'title'       => 'Benchmark product ' . $id,
            'description' => 'Representative export field set ' . $id,
            'price'       => ($id % 10000) / 100,
            'active'      => $id % 2 === 0,
            'created_at'  => $createdAt,
            'updated_at'  => $createdAt,
            'category'    => 'category-' . ($id % 25),
            'stock'       => $id % 500,
        ]);

        $models[] = $model;
    }

    return $models;
}
