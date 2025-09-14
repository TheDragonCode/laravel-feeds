<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Presets\Items;

use DragonCode\LaravelFeed\Feeds\Items\FeedItem;

use function blank;
use function collect;

/** @property-read \Illuminate\Database\Eloquent\Model $model */
class YandexFeedItem extends FeedItem
{
    protected int|string|null $attributeId = null;

    protected bool $attributeAvailable = true;

    protected string $attributeType = 'vendor.model';

    protected string $url;

    protected ?string $barcode = null;

    protected string $title;

    protected string $description;

    protected string $price;

    protected string $currencyId = 'RUR';

    protected ?string $vendor = null;

    protected array $images;

    protected array $additional = [];

    public function name(): string
    {
        return 'offer';
    }

    public function attributeId(int|string|null $id): static
    {
        $this->attributeId = $id;

        return $this;
    }

    public function attributeAvailable(bool $available): static
    {
        $this->attributeAvailable = $available;

        return $this;
    }

    public function attributeType(string $type): static
    {
        $this->attributeType = $type;

        return $this;
    }

    public function url(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function barcode(string $barcode): static
    {
        $this->barcode = $barcode;

        return $this;
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

    public function price(float|string $price): static
    {
        $this->price = (string) $price;

        return $this;
    }

    public function currencyId(string $currencyId): static
    {
        $this->currencyId = $currencyId;

        return $this;
    }

    public function vendor(?string $vendor): static
    {
        $this->vendor = $vendor;

        return $this;
    }

    public function images(array $images): static
    {
        $this->images = $images;

        return $this;
    }

    public function additional(array $additional): static
    {
        $this->additional = $additional;

        return $this;
    }

    public function attributes(): array
    {
        return [
            'id'        => $this->attributeId,
            'available' => $this->attributeAvailable,
            'type'      => $this->attributeType,
        ];
    }

    public function toArray(): array
    {
        return collect([
            'url' => $this->url,

            'barcode'     => $this->barcode,
            'name'        => $this->title,
            'description' => $this->description,

            'price' => $this->price,

            'currencyId' => $this->currencyId,
            'vendor'     => $this->vendor,

            '@picture' => $this->images,
        ])
            ->merge($this->additional)
            ->reject(static fn (mixed $value) => blank($value))
            ->all();
    }
}
