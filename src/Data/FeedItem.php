<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Data;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;

abstract class FeedItem implements Arrayable
{
    public function __construct(
        protected Model $model
    ) {}

    public function toArray(): array
    {
        return $this->model->toArray();
    }
}
