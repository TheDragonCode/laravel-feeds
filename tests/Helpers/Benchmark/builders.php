<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\LazyCollection;

function mockRegressionFeedBuilder(array $models): Builder
{
    $builder = Mockery::mock(Builder::class);

    $builder->shouldNotReceive('count');
    $builder
        ->shouldReceive('lazyById')
        ->once()
        ->with(1000)
        ->andReturn(LazyCollection::make($models));

    return $builder;
}
