<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Presets\Items;

use Carbon\Carbon;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;

/** @property-read \Illuminate\Database\Eloquent\Model $model */
class SitemapFeedItem extends FeedItem
{
    protected string $url;

    protected string $modifiedAt;

    protected float $priority = 0.9;

    public function name(): string
    {
        return 'url';
    }

    public function url(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function modifiedAt(Carbon $updatedAt): static
    {
        $this->modifiedAt = $updatedAt->toIso8601String();

        return $this;
    }

    public function priority(float $priority): static
    {
        $this->priority = $priority;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'loc'      => $this->url,
            'lastmod'  => $this->modifiedAt,
            'priority' => $this->priority,
        ];
    }
}
