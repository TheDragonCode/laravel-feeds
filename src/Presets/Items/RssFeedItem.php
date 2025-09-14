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

    public function guid(int|string $guid): static
    {
        $this->guid = (string) $guid;

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

    public function additional(array $additional): static
    {
        $this->additional = $additional;

        return $this;
    }

    public function toArray(): array
    {
        return collect([
            'title' => $this->title,

            'link' => $this->url,
            'guid' => $this->guid,

            'description' => ['@cdata' => $this->description],

            'category' => $this->category,

            'pubDate' => $this->publishedAt->toRfc1123String(),
        ])
            ->merge($this->additional)
            ->reject(static fn (mixed $value) => blank($value))
            ->all();
    }
}
