<?php

declare(strict_types=1);

app('router')
    ->name('products.show')
    ->get('products/{product}', static fn (string $product) => $product);
