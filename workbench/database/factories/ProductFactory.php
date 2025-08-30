<?php

declare(strict_types=1);

namespace Workbench\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

use function json_encode;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'article' => Str::of(fake()->unique()->password(4, 8))->upper()->prepend('GD-')->toString(),

            'title'       => fake()->unique()->words(4, true),
            'description' => fake()->text(),
            'brand'       => fake()->word(),

            'price'    => fake()->numberBetween(100, 1000),
            'quantity' => fake()->numberBetween(0, 10),
            'currency' => fake()->currencyCode(),

            'images' => json_encode([
                fake()->imageUrl(),
                fake()->imageUrl(),
                fake()->imageUrl(),
            ]),
        ];
    }
}
