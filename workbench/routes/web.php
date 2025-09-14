<?php

declare(strict_types=1);

app('router')
    ->name('products.show')
    ->get('products/{product}', static fn (string $product) => $product);

app('router')
    ->name('news.show')
    ->get('news/{news}', static fn (string $news) => $news);
