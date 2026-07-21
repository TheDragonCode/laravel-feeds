<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\LazyCollection;

function mockRegressionFeedBuilder(array $models): Builder
{
    $builder = Mockery::mock(Builder::class);
    $query   = Mockery::mock(QueryBuilder::class);

    $builder->shouldNotReceive('count');
    $builder
        ->shouldReceive('applyScopes')
        ->once()
        ->andReturnSelf();
    $builder
        ->shouldReceive('withoutGlobalScopes')
        ->once()
        ->andReturnSelf();
    $builder
        ->shouldReceive('getQuery')
        ->once()
        ->andReturn($query);
    $builder
        ->shouldReceive('lazy')
        ->once()
        ->with(1000)
        ->andReturn(LazyCollection::make($models));

    return $builder;
}
