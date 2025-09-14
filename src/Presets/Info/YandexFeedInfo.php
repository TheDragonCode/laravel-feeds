<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Presets\Info;

use DragonCode\LaravelFeed\Feeds\Info\FeedInfo;
use Illuminate\Support\Str;

use function collect;
use function config;

class YandexFeedInfo extends FeedInfo
{
    public ?string $name = null;

    public ?string $company = null;

    public ?string $platform = null;

    public ?string $url = null;

    public ?string $email = null;

    public array $currencies = [
        [
            '@attributes' => [
                'id'   => 'RUR',
                'rate' => '1',
            ],
        ],
    ];

    public array $categories = [];

    public array $additional = [];

    public function name(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function company(string $name): static
    {
        $this->company = $name;

        return $this;
    }

    public function platform(string $name): static
    {
        $this->platform = $name;

        return $this;
    }

    public function url(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function email(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function currency(string $id, float $rate, bool $replace = false): static
    {
        if ($replace) {
            $this->currencies = [];
        }

        $this->currencies[] = [
            '@attributes' => [
                'id'   => Str::upper($id),
                'rate' => $rate,
            ],
        ];

        return $this;
    }

    public function category(int|string $id, string $name, bool $replace = false): static
    {
        if ($replace) {
            $this->categories = [];
        }

        $this->categories[] = [
            '@attributes' => ['id' => $id],
            '@value'      => $name,
        ];

        return $this;
    }

    public function currencies(array $currencies): static
    {
        $this->currencies = [];

        foreach ($currencies as $id => $rate) {
            $this->currency($id, $rate);
        }

        return $this;
    }

    public function categories(array $categories): static
    {
        $this->categories = [];

        foreach ($categories as $id => $name) {
            $this->category($id, $name);
        }

        return $this;
    }

    public function additional(array $data): static
    {
        $this->additional = $data;

        return $this;
    }

    public function toArray(): array
    {
        return collect([
            'name'     => $this->name     ?? config('app.name'),
            'company'  => $this->company  ?? config('app.name'),
            'platform' => $this->platform ?? config('app.name'),

            'url'   => $this->url ?? config('app.url'),
            'email' => $this->email,

            'currencies' => ['@currency' => $this->currencies],
            'categories' => ['@category' => $this->categories],
        ])
            ->merge($this->additional)
            ->reject(static fn (mixed $value) => blank($value))
            ->all();
    }
}
