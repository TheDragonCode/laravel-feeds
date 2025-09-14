<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Presets\Items;

use Carbon\Carbon;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;

use function blank;
use function collect;

/** @property-read \Illuminate\Database\Eloquent\Model $model */
class RssFeedItem extends FeedItem
{
    protected ?string $guid = null;

    protected string $title;

    protected string $url;

    protected string $description;

    protected ?string $category = null;

    protected ?Carbon $publishedAt = null;

    public array $additional = [];

    public function guid(string $guid): static
    {
        $this->guid = $guid;

        return $this;
    }

    public function title(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function url(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function description(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function category(string $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function publishedAt(Carbon $publishedAt): static
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    protected function getGuid(): int|string
    {
        return $this->guid ?? $this->model->getKey();
    }

    protected function getPublishedAt(): Carbon
    {
        return $this->publishedAt ?? $this->model->created_at ?? Carbon::now();
    }

    public function toArray(): array
    {
        return collect([
            'title' => $this->title,

            'link' => $this->url,
            'guid' => $this->getGuid(),

            'description' => ['@cdata' => $this->description],

            'category' => $this->category,

            'pubDate' => $this->getPublishedAt()->toRfc1123String(),
        ])
            ->merge($this->additional)
            ->reject(static fn (mixed $value) => blank($value))
            ->all();
    }
}
