<?php

declare(strict_types=1);

namespace Workbench\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

use function fake;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'article' => Str::of(fake()->unique()->password(4, 8))->upper()->prepend('GD-')->toString(),

            'slug'        => fake()->unique()->slug(),
            'title'       => fake()->unique()->words(4, true),
            'description' => fake()->text(),
            'brand'       => fake()->word(),

            'price'    => fake()->numberBetween(100, 1000),
            'quantity' => fake()->numberBetween(0, 10),
            'currency' => fake()->currencyCode(),

            'images' => [
                fake()->imageUrl(),
                fake()->imageUrl(),
                fake()->imageUrl(),
            ],
        ];
    }
}
