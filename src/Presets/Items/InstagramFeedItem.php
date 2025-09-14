<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Presets\Items;

use DragonCode\LaravelFeed\Feeds\Items\FeedItem;

use function blank;
use function collect;

/** @property-read \Illuminate\Database\Eloquent\Model $model */
class InstagramFeedItem extends FeedItem
{
    protected string $title;

    protected string $description;

    protected string $url;

    protected string $image;

    protected ?array $images = null;

    protected ?string $brand = null;

    protected string $condition = 'new';

    protected string $availability = 'in stock';

    protected float $price;

    protected float $salePrice;

    protected ?string $groupId = null;

    protected string $status = 'active';

    protected ?int $googleCategory = null;

    protected ?int $facebookCategory = null;

    protected array $additional = [];

    public function name(): string
    {
        return 'item';
    }

    public function title(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function description(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function brand(string $name): static
    {
        $this->brand = $name;

        return $this;
    }

    public function url(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function image(?string $url): static
    {
        $this->image = $url;

        return $this;
    }

    public function images(?array $urls): static
    {
        $this->images = $urls;

        return $this;
    }

    public function condition(?string $condition): static
    {
        $this->condition = $condition;

        return $this;
    }

    public function availability(?string $availability): static
    {
        $this->availability = $availability;

        return $this;
    }

    public function price(?float $price, ?float $salePrice = null): static
    {
        $this->price     = $price;
        $this->salePrice = $salePrice ?? $price;

        return $this;
    }

    public function group(int|string|null $id): static
    {
        $this->groupId = (string) $id ?: null;

        return $this;
    }

    public function status(?string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function googleCategory(?int $id): static
    {
        $this->googleCategory = $id;

        return $this;
    }

    public function facebookCategory(?int $id): static
    {
        $this->facebookCategory = $id;

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
            'g:id' => $this->model->getKey(),

            'g:title'       => ['@cdata' => $this->title],
            'g:description' => ['@cdata' => $this->description],

            'g:link'       => $this->url,
            'g:image_link' => $this->image,

            '@g:additional_image_link' => $this->images,

            'g:brand'        => $this->brand,
            'g:condition'    => $this->condition,
            'g:availability' => $this->availability,

            'g:price'      => $this->price,
            'g:sale_price' => $this->salePrice,

            'g:item_group_id' => $this->groupId,

            'g:status' => $this->status,

            'g:google_product_category' => $this->googleCategory,
            'g:fb_product_category'     => $this->facebookCategory,
        ])
            ->merge($this->additional)
            ->reject(static fn (mixed $value) => blank($value))
            ->all();
    }
}
