<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Feeds\Items;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

use function class_basename;

class FeedItem implements Arrayable
{
    protected ?string $name = null;

    public function __construct(
        protected Model $model
    ) {}

    public function name(): string
    {
        return $this->name ??= Str::kebab(class_basename($this->model));
    }

    public function attributes(): array
    {
        return [];
    }

    public function toArray(): array
    {
        return $this->model->toArray();
    }
}
