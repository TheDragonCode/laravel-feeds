<?php

declare(strict_types=1);

use App\Feeds\Transformers\PriceTransformer;
use DragonCode\LaravelFeed\Transformers;

return [
    'transformers' => [
        Transformers\BoolTransformer::class,
        Transformers\DateTimeTransformer::class,
        Transformers\EnumTransformer::class,
        PriceTransformer::class,
    ],
];
