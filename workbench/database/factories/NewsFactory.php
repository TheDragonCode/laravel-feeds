<?php

declare(strict_types=1);

namespace Workbench\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

use function fake;

class NewsFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title'   => fake()->unique()->word(),
            'content' => fake()->text(),

            'category' => fake()->word(),
        ];
    }
}
